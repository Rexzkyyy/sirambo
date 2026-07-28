<style>
    /* Animations */
    @keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in {
        animation: fade-in 0.3s ease-out;
    }

    /* Input Styles */
    .adj-input {
        transition: background-color 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .adj-input.bg-yellow-50 {
        background-color: #fffbeb !important;
        border-color: #fbbf24 !important;
    }

    .adj-input.bg-yellow-50:focus {
        background-color: #fef3c7 !important;
        border-color: #f59e0b !important;
    }

    .adj-input:focus {
        background-color: white !important;
        border-color: #3b82f6 !important;
        box-shadow: 0 0 0 1px rgba(59, 130, 246, 0.2);
    }

    .adj-input:disabled,
    .adj-input[readonly] {
        background-color: #f3f4f6 !important;
        cursor: not-allowed !important;
    }

    /* Table Styles */
    .rekon-table {
        border-collapse: separate;
        border-spacing: 0;
        width: max-content;
        min-width: 100%;
    }

    .rekon-table th,
    .rekon-table td {
        min-width: 72px;
    }

    .rekon-table th.sticky-left,
    .rekon-table td.sticky-left {
        min-width: 260px !important;
        max-width: 260px !important;
        width: 260px !important;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .rekon-table thead th {
        position: sticky;
        background-color: #f9fafb !important;
        z-index: 100;
        border: 1px solid #d1d5db !important;
    }

    .rekon-table thead tr:nth-child(1) th {
        top: 0;
        z-index: 200;
    }

    .rekon-table thead tr:nth-child(2) th {
        top: 33px;
        z-index: 190;
    }

    .rekon-table thead tr:nth-child(3) th {
        top: 57px;
        z-index: 180;
    }

    .rekon-table thead tr:nth-child(4) th {
        top: 81px;
        z-index: 170;
    }

    .rekon-table thead th.sticky-left {
        top: 0 !important;
        left: 0;
        z-index: 210 !important;
        background-color: #ffffff !important;
        border-right: 2px solid #94a3b8 !important;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
    }

    .rekon-table td.sticky-left {
        position: sticky;
        left: 0;
        z-index: 30 !important;
        border-right: 2px solid #cbd5e1 !important;
        box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
    }

    /* Row Hover */
    .rekon-table tbody tr {
        transition: background-color 0.15s ease, outline 0.15s ease, box-shadow 0.15s ease;
    }

    .rekon-table tbody tr:hover {
        outline: 2px solid #fbbf24;
        outline-offset: -2px;
        box-shadow: 0 0 0 1px rgba(251, 191, 36, 0.3);
    }

    .rekon-table tbody tr:hover td.sticky-left {
        font-weight: 600;
        box-shadow: inset 0 0 0 1px rgba(251, 191, 36, 0.5);
    }

    /* Zebra Striping for Sticky Column */
    .rekon-table tbody tr:nth-child(even) {
        background-color: #f9fafb !important;
    }

    .rekon-table tbody tr:nth-child(even) td.sticky-left {
        background-color: #f9fafb !important;
    }

    .rekon-table tbody tr:nth-child(odd) td.sticky-left {
        background-color: #ffffff !important;
    }

    /* Status Colors */
    .bg-red-strong {
        background-color: #dc2626 !important;
        color: #ffffff !important;
        border-color: #991b1b !important;
    }

    .bg-yellow-strong {
        background-color: #facc15 !important;
        color: #000000 !important;
        border-color: #ca8a04 !important;
    }

    .bg-orange-strong {
        background-color: #ea580c !important;
        color: #ffffff !important;
        border-color: #9a3412 !important;
    }

    .bg-green-soft,
    .sign-positive {
        background-color: #16a34a !important;
        color: #ffffff !important;
    }

    .bg-orange-soft,
    .sign-negative {
        background-color: #f97316 !important;
        color: #ffffff !important;
    }

    /* Province & Total Kab/Kota Row Colors */
    tr.row-provinsi td:not(.bg-red-strong):not(.bg-yellow-strong):not(.bg-orange-strong):not(.bg-green-soft):not(.bg-orange-soft),
    tr.row-provinsi td.sticky-left {
        background-color: #eff6ff !important;
    }

    tr.row-total-kabkota td:not(.bg-red-strong):not(.bg-yellow-strong):not(.bg-orange-strong):not(.bg-green-soft):not(.bg-orange-soft),
    tr.row-total-kabkota td.sticky-left {
        background-color: #f0fdf4 !important;
    }

    /* Tooltip */
    td[title] {
        position: relative;
    }

    td[title]:hover::after {
        content: attr(title);
        position: absolute;
        bottom: 100%;
        left: 50%;
        transform: translateX(-50%);
        background: #374151;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        white-space: nowrap;
        z-index: 1000;
        margin-bottom: 5px;
    }

    /* Status Popup */
    .status-cell {
        position: relative;
    }

    .status-tooltip {
        position: absolute;
        top: 2px;
        right: 2px;
        z-index: 20;
    }

    .status-badge {
        width: 14px;
        height: 14px;
        border-radius: 999px;
        background: #111827;
        color: #ffffff;
        font-size: 9px;
        line-height: 14px;
        text-align: center;
        font-weight: bold;
        cursor: help;
    }

    .status-popup {
        display: none;
        position: absolute;
        top: 18px;
        right: 0;
        background: #111827;
        color: #ffffff;
        padding: 6px 8px;
        border-radius: 6px;
        font-size: 11px;
        min-width: 180px;
        z-index: 1000;
    }

    .status-tooltip:hover .status-popup {
        display: block;
    }
</style> box-shadow: 0 4px 10px rgba(0,0,0,0.15);
}
.status-tooltip:hover .status-popup { display: block; }
</style>