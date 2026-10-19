<style>
    /* Shared letterhead layout: centred logo on top, address line at the foot of every page. */
    @page { margin: 118px 66px 84px 66px; }
    * { box-sizing: border-box; }
    body { margin: 0; font-family: DejaVu Sans, sans-serif; font-size: 11px; line-height: 1.45; color: #000; }

    .header { position: fixed; top: -104px; left: 0; right: 0; height: 66px; text-align: center; }
    .logo { height: 56px; max-width: 220px; }
    .footer { position: fixed; bottom: -58px; left: 0; right: 0; font-size: 10.5px; color: #000; text-align: left; }

    p { margin: 0 0 10px; }
    .bold { font-weight: bold; }
    .heading { margin: 0 0 12px; font-weight: bold; }
    .section { margin: 16px 0 4px; font-weight: bold; page-break-after: avoid; }
    .center { text-align: center; }

    ul { margin: 2px 0 8px; padding-left: 38px; }
    li { margin: 0 0 1px; page-break-inside: avoid; }

    .nobreak { page-break-inside: avoid; }
    .blank { display: inline-block; min-width: 150px; border-bottom: 1px solid #000; }
    .signed { font-style: italic; font-weight: bold; font-size: 13px; }
    .watermark { position: fixed; top: 40%; left: 12%; width: 76%; text-align: center; font-size: 72px; font-weight: bold; letter-spacing: 8px; color: rgba(107, 114, 128, 0.09); z-index: -1000; }
</style>
