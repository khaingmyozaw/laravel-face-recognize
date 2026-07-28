export type EnrollmentImage = {
    id: number;
    enrollment_id: number;
    file_path: string;
    url: string;
    created_at: string | null;
    updated_at: string | null;
};

export type Enrollment = {
    id: number;
    name: string;
    father_name: string;
    mother_name: string;
    nrc: string | null;
    is_f_alive: boolean;
    is_m_alive: boolean;
    remark: string | null;
    address: string;
    images: EnrollmentImage[];
    created_at: string | null;
    updated_at: string | null;
};

export type PaginatedEnrollments = {
    data: Enrollment[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
};
