/**
 * Soft, professional badge color tokens. The backend enums (AssetStatus,
 * PartStatus, LoanStatus, AuditItemStatus) expose a matching `color()` value
 * so the frontend never has to duplicate status → color decisions.
 */
export const statusColorClasses: Record<string, string> = {
    green: 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-400 dark:border-emerald-500/20',
    blue: 'bg-sky-50 text-sky-700 border-sky-200 dark:bg-sky-500/10 dark:text-sky-400 dark:border-sky-500/20',
    red: 'bg-red-50 text-red-700 border-red-200 dark:bg-red-500/10 dark:text-red-400 dark:border-red-500/20',
    amber: 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-500/10 dark:text-amber-400 dark:border-amber-500/20',
    slate: 'bg-slate-100 text-slate-700 border-slate-200 dark:bg-slate-500/10 dark:text-slate-400 dark:border-slate-500/20',
    gray: 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-500/10 dark:text-gray-400 dark:border-gray-500/20',
};

export function statusColorClass(color?: string | null): string {
    return statusColorClasses[color ?? 'gray'] ?? statusColorClasses.gray;
}
