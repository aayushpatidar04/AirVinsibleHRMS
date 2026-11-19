/** Minutes as "7h 30m" (or "—" when there is nothing to show). */
export const formatMinutes = (minutes) => {
    const m = Math.round(Number(minutes) || 0);
    if (m <= 0) return "—";
    const h = Math.floor(m / 60);
    const rest = m % 60;
    if (h === 0) return `${rest}m`;
    return rest === 0 ? `${h}h` : `${h}h ${rest}m`;
};

/** Indian digit grouping: 1234567.5 => "12,34,567.50" (whole numbers have no decimals). */
export const formatMoney = (value, forceDecimals = false) => {
    const n = Number(value) || 0;
    const whole = Number.isInteger(n) && !forceDecimals;
    return n.toLocaleString("en-IN", {
        minimumFractionDigits: whole ? 0 : 2,
        maximumFractionDigits: 2,
    });
};

export const flagLabels = {
    missing_in: "Missing punch-in",
    missing_out: "Missing punch-out",
    missing_portal: "No portal punch",
    missing_machine: "No machine punch",
    source_conflict: "Portal and machine disagree",
    geo_exception: "Location not verified",
    worked_on_leave: "Worked on leave",
    short_session: "Short system session",
    open_break: "Break not ended",
    break_exceeded: "Break over limit",
    short_hours: "Under half-day hours",
};

export const punchLabels = {
    in: "Punch in",
    out: "Punch out",
    break_start: "Break start",
    break_end: "Break end",
};
