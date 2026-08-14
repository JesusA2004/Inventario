export type PartFormOptions = {
    companies: { id: number; name: string }[];
    branches: { id: number; name: string; company_id: number }[];
    brands: { id: number; name: string }[];
    responsiblePeople: { id: number; full_name: string; company_id: number }[];
    statuses: { value: string; label: string; color: string }[];
};

export type PartFormData = {
    company_id: number | string;
    branch_id: number | string;
    related_asset_id: number | string;
    internal_code: string;
    name: string;
    brand_id: number | string;
    serial_number: string;
    part_number: string;
    status: string;
    in_inventory: boolean;
    quantity: number;
    specifications: string;
    assembled: boolean;
    notes: string;
    purchase_date: string | null;
    responsible_id: number | string;
    invoice_url: string;
    needs_label: boolean;
};
