export type StatusOption = { value: string; label: string; color: string };

export type CompanyOption = { id: number; name: string; code: string };
export type BranchOption = { id: number; name: string; company_id: number };
export type DepartmentOption = {
    id: number;
    name: string;
    company_id: number | null;
};
export type AssetTypeOption = { id: number; name: string; code: string };
export type BrandOption = { id: number; name: string };
export type ResponsibleOption = {
    id: number;
    full_name: string;
    company_id: number;
    branch_id: number | null;
    department_id: number | null;
};

export type AssetFormOptions = {
    companies: CompanyOption[];
    branches: BranchOption[];
    departments: DepartmentOption[];
    assetTypes: AssetTypeOption[];
    brands: BrandOption[];
    responsiblePeople: ResponsibleOption[];
    statuses: StatusOption[];
};

export type AssetListItem = {
    id: number;
    public_id: string;
    internal_code: string;
    name: string;
    model: string | null;
    serial_number: string | null;
    status: StatusOption | null;
    in_inventory: boolean;
    company: { id: number; name: string; code: string } | null;
    branch: { id: number; name: string } | null;
    department: { id: number; name: string } | null;
    brand: { id: number; name: string } | null;
    assetType: { id: number; name: string } | null;
    currentResponsible: { id: number; full_name: string } | null;
    acquired_at: string | null;
    last_reviewed_at: string | null;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: PaginationLink[];
};

export type AssetFormData = {
    company_id: number | string;
    branch_id: number | string;
    department_id: number | string;
    asset_type_id: number | string;
    name: string;
    brand_id: number | string;
    model: string;
    serial_number: string;
    internal_code: string;
    status: string;
    in_inventory: boolean;
    current_responsible_id: number | string;
    delivered_by_responsible_id: number | string;
    components: string;
    specifications: string;
    notes: string;
    invoice_url: string;
    invoice_file: File | null;
    photos: File[];
    purchase_date: string | null;
    acquired_at: string | null;
    last_reviewed_at: string | null;
    create_another?: boolean;
};
