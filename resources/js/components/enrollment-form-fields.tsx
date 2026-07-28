import { useState } from 'react';
import { ImageDropzone } from '@/components/image-dropzone';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Enrollment } from '@/types';

type EnrollmentFormFieldsProps = {
    enrollment?: Enrollment;
    errors: Record<string, string>;
    processing: boolean;
    submitLabel: string;
};

export function EnrollmentFormFields({
    enrollment,
    errors,
    processing,
    submitLabel,
}: EnrollmentFormFieldsProps) {
    const [fatherAlive, setFatherAlive] = useState(
        enrollment?.is_f_alive ?? true,
    );
    const [motherAlive, setMotherAlive] = useState(
        enrollment?.is_m_alive ?? true,
    );

    return (
        <div className="space-y-6">
            <div className="grid gap-6 md:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="name">Name</Label>
                    <Input
                        id="name"
                        name="name"
                        required
                        defaultValue={enrollment?.name ?? ''}
                        placeholder="Full name"
                    />
                    <InputError message={errors.name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="nrc">NRC</Label>
                    <Input
                        id="nrc"
                        name="nrc"
                        defaultValue={enrollment?.nrc ?? ''}
                        placeholder="Optional"
                    />
                    <InputError message={errors.nrc} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="father_name">Father name</Label>
                    <Input
                        id="father_name"
                        name="father_name"
                        required
                        defaultValue={enrollment?.father_name ?? ''}
                    />
                    <InputError message={errors.father_name} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="mother_name">Mother name</Label>
                    <Input
                        id="mother_name"
                        name="mother_name"
                        required
                        defaultValue={enrollment?.mother_name ?? ''}
                    />
                    <InputError message={errors.mother_name} />
                </div>

                <div className="flex items-center gap-3">
                    <input
                        type="hidden"
                        name="is_f_alive"
                        value={fatherAlive ? '1' : '0'}
                    />
                    <Checkbox
                        id="is_f_alive"
                        checked={fatherAlive}
                        onCheckedChange={(checked) =>
                            setFatherAlive(checked === true)
                        }
                    />
                    <Label htmlFor="is_f_alive">Father is alive</Label>
                    <InputError message={errors.is_f_alive} />
                </div>

                <div className="flex items-center gap-3">
                    <input
                        type="hidden"
                        name="is_m_alive"
                        value={motherAlive ? '1' : '0'}
                    />
                    <Checkbox
                        id="is_m_alive"
                        checked={motherAlive}
                        onCheckedChange={(checked) =>
                            setMotherAlive(checked === true)
                        }
                    />
                    <Label htmlFor="is_m_alive">Mother is alive</Label>
                    <InputError message={errors.is_m_alive} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="address">Address</Label>
                <Input
                    id="address"
                    name="address"
                    required
                    defaultValue={enrollment?.address ?? ''}
                />
                <InputError message={errors.address} />
            </div>

            <div className="grid gap-2">
                <Label htmlFor="remark">Remark</Label>
                <Input
                    id="remark"
                    name="remark"
                    defaultValue={enrollment?.remark ?? ''}
                    placeholder="Optional"
                />
                <InputError message={errors.remark} />
            </div>

            <ImageDropzone errors={errors} />

            <div className="flex justify-end">
                <Button type="submit" disabled={processing}>
                    {processing ? 'Saving...' : submitLabel}
                </Button>
            </div>
        </div>
    );
}
