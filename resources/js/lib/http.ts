/**
 * Minimal JSON POST helper for "quick create" flows (e.g. creating a
 * responsable from inside the asset form) that need the created record
 * back in JS without a full Inertia page visit/redirect.
 */

export type ApiValidationError = {
    status: number;
    errors: Record<string, string[]>;
    message?: string;
};

function getCookie(name: string): string | null {
    const match = document.cookie.match(new RegExp(`(?:^|; )${name}=([^;]*)`));

    return match ? decodeURIComponent(match[1]) : null;
}

export async function getJson<T>(
    url: string,
    params: Record<string, string | number | undefined> = {},
): Promise<T> {
    const query = new URLSearchParams();

    for (const [key, value] of Object.entries(params)) {
        if (value !== undefined && value !== null && value !== '') {
            query.set(key, String(value));
        }
    }

    const response = await fetch(
        `${url}${query.toString() ? `?${query.toString()}` : ''}`,
        {
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        },
    );

    return (await response.json()) as T;
}

export async function postJson<T>(
    url: string,
    data: Record<string, unknown>,
): Promise<T> {
    const token = getCookie('XSRF-TOKEN');

    const response = await fetch(url, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(token ? { 'X-XSRF-TOKEN': token } : {}),
        },
        body: JSON.stringify(data),
    });

    const payload = await response.json().catch(() => null);

    if (!response.ok) {
        const error: ApiValidationError = {
            status: response.status,
            errors: payload?.errors ?? {},
            message: payload?.message,
        };

        throw error;
    }

    return payload as T;
}
