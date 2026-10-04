// Format integer rupiah to display string: 1250000 → "Rp 1.250.000"
export function useMoney() {
    const fmt = new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 });
    const format = (amount: number) => fmt.format(amount);
    const compact = (amount: number) => {
        if (Math.abs(amount) >= 1_000_000) return `Rp ${(amount / 1_000_000).toFixed(1)}jt`;
        if (Math.abs(amount) >= 1_000) return `Rp ${(amount / 1_000).toFixed(0)}rb`;
        return format(amount);
    };
    return { format, compact };
}
