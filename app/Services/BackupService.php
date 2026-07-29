<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

class BackupService
{
    /**
     * @throws Throwable
     */
    public function downloadDatabase(string $format = 'sql'): BinaryFileResponse
    {
        $format = $format === 'zip' ? 'zip' : 'sql';
        $timestamp = now()->format('Ymd_His');
        $sqlPath = $this->backupPath("database_{$timestamp}.sql");

        $this->createDatabaseDump($sqlPath);

        if ($format === 'sql') {
            return $this->downloadAndDelete($sqlPath, "database_{$timestamp}.sql");
        }

        $zipPath = $this->backupPath("database_{$timestamp}.sql.zip");
        $this->zipSingleFile($sqlPath, $zipPath, "database_{$timestamp}.sql");
        @unlink($sqlPath);

        return $this->downloadAndDelete($zipPath, "database_{$timestamp}.sql.zip");
    }

    /**
     * @throws Throwable
     */
    public function downloadEnrollmentImages(): BinaryFileResponse
    {
        $timestamp = now()->format('Ymd_His');
        $zipPath = $this->backupPath("enrollment-images_{$timestamp}.zip");
        $sourceRoot = Storage::disk('public')->path('enrollments');

        $this->zipDirectory($sourceRoot, $zipPath, 'enrollments');

        return $this->downloadAndDelete($zipPath, "enrollment-images_{$timestamp}.zip");
    }

    private function createDatabaseDump(string $destination): void
    {
        $driver = (string) config('database.default');

        match ($driver) {
            'mysql', 'mariadb' => $this->dumpMysql($destination),
            'sqlite' => $this->dumpSqlite($destination),
            default => throw new RuntimeException("Unsupported database driver [{$driver}] for backups."),
        };
    }

    private function dumpMysql(string $destination): void
    {
        $connection = (string) config('database.default');
        $config = config("database.connections.{$connection}");

        if (! is_array($config)) {
            throw new RuntimeException('Database connection is not configured.');
        }

        $defaultsFile = $this->writeMysqlDefaultsFile($config);

        try {
            $process = new Process([
                'mysqldump',
                '--defaults-extra-file='.$defaultsFile,
                '--single-transaction',
                '--routines',
                '--triggers',
                (string) ($config['database'] ?? ''),
            ]);
            $process->setTimeout(300);
            $process->run();

            if (! $process->isSuccessful()) {
                throw new RuntimeException(trim($process->getErrorOutput()) ?: 'mysqldump failed.');
            }

            if (file_put_contents($destination, $process->getOutput()) === false) {
                throw new RuntimeException('Unable to write database dump file.');
            }
        } finally {
            @unlink($defaultsFile);
        }
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function writeMysqlDefaultsFile(array $config): string
    {
        $path = $this->backupPath('mysqldump_'.uniqid('', true).'.cnf');
        $contents = "[client]\n"
            ."host=\"{$this->escapeCnf((string) ($config['host'] ?? '127.0.0.1'))}\"\n"
            ."port=\"{$this->escapeCnf((string) ($config['port'] ?? '3306'))}\"\n"
            ."user=\"{$this->escapeCnf((string) ($config['username'] ?? ''))}\"\n"
            ."password=\"{$this->escapeCnf((string) ($config['password'] ?? ''))}\"\n";

        if (file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Unable to write mysqldump credentials file.');
        }

        chmod($path, 0600);

        return $path;
    }

    private function escapeCnf(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }

    private function dumpSqlite(string $destination): void
    {
        $database = (string) config('database.connections.sqlite.database');

        if ($database === ':memory:' || str_starts_with($database, 'file:')) {
            $this->dumpSqliteFromPdo($destination);

            return;
        }

        if (! is_file($database)) {
            throw new RuntimeException('SQLite database file was not found.');
        }

        $process = new Process(['sqlite3', $database, '.dump']);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($process->getErrorOutput()) ?: 'sqlite3 dump failed.');
        }

        if (file_put_contents($destination, $process->getOutput()) === false) {
            throw new RuntimeException('Unable to write database dump file.');
        }
    }

    private function dumpSqliteFromPdo(string $destination): void
    {
        $pdo = DB::connection()->getPdo();
        $handle = fopen($destination, 'wb');

        if ($handle === false) {
            throw new RuntimeException('Unable to open dump file for writing.');
        }

        try {
            fwrite($handle, "PRAGMA foreign_keys=OFF;\nBEGIN TRANSACTION;\n");
            $this->writeSqliteSchemaAndData($pdo, $handle);
            fwrite($handle, "COMMIT;\n");
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  resource  $handle
     */
    private function writeSqliteSchemaAndData(\PDO $pdo, $handle): void
    {
        $objects = $pdo->query(
            "SELECT type, name, sql FROM sqlite_master
             WHERE sql IS NOT NULL
               AND name NOT LIKE 'sqlite_%'
             ORDER BY CASE type WHEN 'table' THEN 0 WHEN 'index' THEN 1 ELSE 2 END, name",
        );

        if ($objects === false) {
            throw new RuntimeException('Unable to read SQLite schema.');
        }

        foreach ($objects as $object) {
            fwrite($handle, $object['sql'].";\n");

            if ($object['type'] !== 'table') {
                continue;
            }

            $this->writeSqliteTableRows($pdo, $handle, (string) $object['name']);
        }
    }

    /**
     * @param  resource  $handle
     */
    private function writeSqliteTableRows(\PDO $pdo, $handle, string $table): void
    {
        $statement = $pdo->query('SELECT * FROM "'.str_replace('"', '""', $table).'"');

        if ($statement === false) {
            throw new RuntimeException("Unable to read table [{$table}].");
        }

        while ($row = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $columns = array_map(
                fn (string $column): string => '"'.str_replace('"', '""', $column).'"',
                array_keys($row),
            );
            $values = array_map(fn (mixed $value): string => $this->sqliteLiteral($value), array_values($row));
            fwrite($handle, 'INSERT INTO "'.str_replace('"', '""', $table).'" ('
                .implode(', ', $columns).') VALUES ('.implode(', ', $values).");\n");
        }
    }

    private function sqliteLiteral(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return "'".str_replace("'", "''", (string) $value)."'";
    }

    private function zipSingleFile(string $sourcePath, string $zipPath, string $entryName): void
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create zip archive.');
        }

        if (! $zip->addFile($sourcePath, $entryName)) {
            $zip->close();
            throw new RuntimeException('Unable to add file to zip archive.');
        }

        $zip->close();
    }

    private function zipDirectory(string $sourceRoot, string $zipPath, string $rootPrefix): void
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Unable to create zip archive.');
        }

        if (! is_dir($sourceRoot)) {
            $zip->addEmptyDir($rootPrefix);
            $zip->close();

            return;
        }

        $this->addDirectoryToZip($zip, $sourceRoot, $rootPrefix);
        $zip->close();
    }

    private function addDirectoryToZip(ZipArchive $zip, string $sourceRoot, string $rootPrefix): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceRoot, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        $added = false;

        foreach ($iterator as $item) {
            $relative = $rootPrefix.'/'.ltrim(
                str_replace('\\', '/', substr($item->getPathname(), strlen($sourceRoot))),
                '/',
            );

            if ($item->isDir()) {
                $zip->addEmptyDir($relative);
                $added = true;

                continue;
            }

            $zip->addFile($item->getPathname(), $relative);
            $added = true;
        }

        if (! $added) {
            $zip->addEmptyDir($rootPrefix);
        }
    }

    private function backupPath(string $fileName): string
    {
        $directory = Storage::disk('local')->path('backups');

        if (! File::isDirectory($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        return $directory.DIRECTORY_SEPARATOR.$fileName;
    }

    private function downloadAndDelete(string $path, string $downloadName): BinaryFileResponse
    {
        return response()
            ->download($path, $downloadName)
            ->deleteFileAfterSend(true);
    }
}
