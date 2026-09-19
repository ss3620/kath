#!/usr/bin/env python3
"""Generate Earth Goddess AU Phase 1 full site flow test PDF."""

from pathlib import Path

from fpdf import FPDF


OUT = Path(__file__).with_name("EG-Phase1-Full-Site-Test-Flow.pdf")


class PDF(FPDF):
    def header(self):
        if self.page_no() == 1:
            return
        self.set_font("Helvetica", "I", 8)
        self.set_text_color(100, 100, 100)
        self.cell(0, 6, "Earth Goddess AU - Phase 1 Full Site Test Flow", align="L")
        self.ln(8)

    def footer(self):
        self.set_y(-12)
        self.set_font("Helvetica", "I", 8)
        self.set_text_color(120, 120, 120)
        self.cell(0, 8, f"Page {self.page_no()}/{{nb}}  |  Staging: https://staging.earthgoddess.com.au/", align="C")

    def h1(self, text):
        self.set_font("Helvetica", "B", 16)
        self.set_text_color(59, 31, 74)
        self.multi_cell(0, 8, text)
        self.ln(2)

    def h2(self, text):
        self.set_x(self.l_margin)
        self.ln(2)
        self.set_font("Helvetica", "B", 12)
        self.set_text_color(59, 31, 74)
        self.multi_cell(0, 7, text)
        self.ln(1)

    def h3(self, text):
        self.set_x(self.l_margin)
        self.set_font("Helvetica", "B", 10)
        self.set_text_color(40, 40, 40)
        self.multi_cell(0, 6, text)
        self.ln(0.5)

    def body(self, text):
        self.set_x(self.l_margin)
        self.set_font("Helvetica", "", 9)
        self.set_text_color(30, 30, 30)
        self.multi_cell(0, 5, text)
        self.ln(1)

    def bullet(self, text):
        self.set_x(self.l_margin)
        self.set_font("Helvetica", "", 9)
        self.set_text_color(30, 30, 30)
        self.multi_cell(0, 5, f"- {text}")

    def table(self, headers, rows, col_widths=None):
        if col_widths is None:
            usable = self.epw
            col_widths = [usable / len(headers)] * len(headers)

        line_h = 5
        self.set_font("Helvetica", "B", 8)
        self.set_fill_color(91, 58, 110)
        self.set_text_color(255, 255, 255)
        for i, h in enumerate(headers):
            self.cell(col_widths[i], 7, h, border=1, fill=True)
        self.ln()

        self.set_font("Helvetica", "", 8)
        self.set_text_color(30, 30, 30)
        fill = False
        for row in rows:
            # Estimate row height from wrapped text
            heights = []
            for i, cell in enumerate(row):
                lines = self.multi_cell(col_widths[i], line_h, str(cell), dry_run=True, output="LINES")
                heights.append(len(lines) * line_h)
            row_h = max(heights + [line_h])
            if self.get_y() + row_h > self.page_break_trigger:
                self.add_page()
                self.set_font("Helvetica", "B", 8)
                self.set_fill_color(91, 58, 110)
                self.set_text_color(255, 255, 255)
                for i, h in enumerate(headers):
                    self.cell(col_widths[i], 7, h, border=1, fill=True)
                self.ln()
                self.set_font("Helvetica", "", 8)
                self.set_text_color(30, 30, 30)

            x0, y0 = self.get_x(), self.get_y()
            if fill:
                self.set_fill_color(245, 240, 248)
            else:
                self.set_fill_color(255, 255, 255)
            for i, cell in enumerate(row):
                x = x0 + sum(col_widths[:i])
                self.rect(x, y0, col_widths[i], row_h, style="DF")
                self.set_xy(x, y0)
                self.multi_cell(col_widths[i], line_h, str(cell))
            self.set_xy(x0, y0 + row_h)
            fill = not fill
        self.set_x(self.l_margin)
        self.ln(3)


def build():
    pdf = PDF(orientation="P", unit="mm", format="A4")
    pdf.alias_nb_pages()
    pdf.set_auto_page_break(auto=True, margin=15)
    pdf.add_page()

    pdf.h1("Earth Goddess AU")
    pdf.h2("Phase 1 - Full Site Test Flow")
    pdf.body(
        "Complete end-to-end manual test plan for VIP, Affiliate, Ambassador, Wholesale, "
        "wholesale referral, and UX smoke checks. Use two browsers (or Normal + Incognito) "
        "so affiliate cookies and roles do not collide."
    )
    pdf.body("Environment: https://staging.earthgoddess.com.au/")
    pdf.body("Admin: WP Admin > EG Phase 1 / EG Applications / Users / WooCommerce / Affiliates")
    pdf.body("Payment: Visa test card 4242 4242 4242 4242 (or any method that reaches Processing/Completed)")
    pdf.body("AU test address: 123 Collins Street, Suite 1, Melbourne VIC 3000 | Phone: 0412 345 678")
    pdf.body("Cache purge (SSH): wp litespeed-purge all   OR   wp cache flush")

    # Credentials
    pdf.h2("1. User credentials by role")
    pdf.body("Shared password for all @earthgoddess.test QA accounts:")
    pdf.h3("Password: EgQaTest!2026")
    pdf.ln(1)

    w = pdf.epw
    pdf.table(
        ["Role", "Email", "Password", "Use for"],
        [
            ["VIP Shopper", "qa-vip@earthgoddess.test", "EgQaTest!2026", "Pathway A - VIP % + points"],
            ["Customer / Buyer", "qa-buyer@earthgoddess.test", "EgQaTest!2026", "Buy via referral links"],
            ["Affiliate L1", "qa-aff-l1@earthgoddess.test", "EgQaTest!2026", "Pathway B / E referral"],
            ["Affiliate L2", "qa-aff-l2@earthgoddess.test", "EgQaTest!2026", "Multi-tier B4"],
            ["Affiliate L3", "qa-aff-l3@earthgoddess.test", "EgQaTest!2026", "Multi-tier B5"],
            ["Affiliate L4", "qa-aff-l4@earthgoddess.test", "EgQaTest!2026", "Multi-tier B5"],
            ["Ambassador", "qa-amb@earthgoddess.test", "EgQaTest!2026", "Pathway C"],
            ["Wholesale Starter", "qa-ws-starter@earthgoddess.test", "EgQaTest!2026", "Pathway D - 35% + $250 min"],
            ["Wholesale Preferred", "qa-ws-pref@earthgoddess.test", "EgQaTest!2026", "Pathway D - 40%"],
            ["Wholesale Elite", "qa-ws-elite@earthgoddess.test", "EgQaTest!2026", "Pathway D - 45%"],
        ],
        col_widths=[w * 0.20, w * 0.32, w * 0.18, w * 0.30],
    )

    pdf.h3("Manual test accounts used on staging (own passwords)")
    pdf.table(
        ["Role", "Email", "Notes"],
        [
            ["Affiliate (manual)", "ss3620@gmail.com", "Approved Affiliate; ref example ?ref=7248"],
            ["Ambassador (manual)", "shailendra.strgh@gmail.com", "Approved Ambassador; keep separate from wholesale"],
        ],
        col_widths=[w * 0.25, w * 0.35, w * 0.40],
    )
    pdf.body(
        "Important: Do not use the same account as affiliate/ambassador AND buyer for that referral "
        "(self-referral usually blocks commission). Applications require an existing My Account email."
    )

    # Prep
    pdf.add_page()
    pdf.h2("2. Prep (once)")
    for step in [
        "Log into WP Admin.",
        "Open EG Phase 1 - install status OK (reseed only if MISSING).",
        "Purge LiteSpeed cache.",
        "Confirm QA accounts exist (or run live pathway prep / create users).",
        "Open a results sheet: ID | Steps | Expected | Actual | Pass/Fail | Order or User ID | Screenshot.",
    ]:
        pdf.bullet(step)

    pdf.h2("3. Key URLs")
    pdf.table(
        ["Page", "URL"],
        [
            ["Home", "https://staging.earthgoddess.com.au/"],
            ["Shop", "https://staging.earthgoddess.com.au/shop/"],
            ["My Account", "https://staging.earthgoddess.com.au/my-account/"],
            ["Affiliate apply", "https://staging.earthgoddess.com.au/affiliate-business-builder-application/"],
            ["Ambassador apply", "https://staging.earthgoddess.com.au/ambassador-application/"],
            ["Wholesale apply", "https://staging.earthgoddess.com.au/wholesale-partner-application/"],
        ],
        col_widths=[w * 0.28, w * 0.72],
    )

    # Pathway A
    pdf.h2("4. Pathway A - VIP Shopper (loyalty + member %)")
    pdf.body("Account: qa-vip@earthgoddess.test / EgQaTest!2026")
    pdf.table(
        ["ID", "Steps", "Expected", "Pass?"],
        [
            ["A1", "Register/use VIP. Check WPLoyalty balance", "+50 signup points (if campaign active)", "[ ]"],
            ["A2", "Spend under $500. Add ~$50 product to cart", "VIP Member Discount 10%", "[ ]"],
            ["A3", "Complete order", "Points ~1 per $1", "[ ]"],
            ["A4", "Submit product review", "+50 points", "[ ]"],
            ["A5", "Raise trailing spend to $500-$999; refresh cart", "Discount 12% (Star)", "[ ]"],
            ["A6", "Raise spend to >= $1000; refresh cart", "Discount 15% (Goddess)", "[ ]"],
            ["A7", "Redeem loyalty reward/coupon", "Reward applies; points decrease", "[ ]"],
            ["A8", "Login as Wholesale; add product", "No VIP Member Discount fee", "[ ]"],
        ],
        col_widths=[w * 0.08, w * 0.40, w * 0.40, w * 0.12],
    )

    # Pathway B
    pdf.h2("5. Pathway B - Affiliate Business Builder")
    pdf.body("QA: qa-aff-l1@earthgoddess.test  |  Manual: ss3620@gmail.com")
    pdf.table(
        ["ID", "Steps", "Expected", "Pass?"],
        [
            ["B1", "Submit /affiliate-business-builder-application/", "Success; EG Applications pending", "[ ]"],
            ["B2", "Admin Approve Application", "Role Affiliate Business Builder; Clear Quartz tag; Affiliate in My Account", "[ ]"],
            ["B3", "Buyer via L1 link (Incognito) completes order", "L1 ~15% unpaid commission", "[ ]"],
            ["B4", "Retag Amethyst; create L2; buy via L2 link", "L2 personal commission; L1 parent ~3%", "[ ]"],
            ["B5", "Optional Green Aventurine / Moonstone deep tree", "Parent splits 5|3 or 5|4|2", "[ ]"],
            ["B6", "SOP only", "Active Partner >= $100 SV / 30 days; no auto-upgrade", "[ ]"],
        ],
        col_widths=[w * 0.08, w * 0.40, w * 0.40, w * 0.12],
    )

    # Pathway C
    pdf.h2("6. Pathway C - Ambassador (no downline)")
    pdf.body("QA: qa-amb@earthgoddess.test  |  Manual: shailendra.strgh@gmail.com")
    pdf.table(
        ["ID", "Steps", "Expected", "Pass?"],
        [
            ["C1", "Submit /ambassador-application/", "Pending in EG Applications", "[ ]"],
            ["C2", "Admin Approve", "Role Ambassador; Seed Ambassador tag", "[ ]"],
            ["C3", "Buyer via ambassador referral link", "Commission ~20% Seed", "[ ]"],
            ["C4", "Retag Bloom; new referred order", "Commission ~25%", "[ ]"],
            ["C5", "Retag Goddess; new referred order", "Commission ~30%", "[ ]"],
            ["C6", "If child linked under ambassador", "No parent commission", "[ ]"],
        ],
        col_widths=[w * 0.08, w * 0.40, w * 0.40, w * 0.12],
    )

    # Pathway D
    pdf.add_page()
    pdf.h2("7. Pathway D - Wholesale Partner")
    pdf.body(
        "Accounts: qa-ws-starter / qa-ws-pref / qa-ws-elite @earthgoddess.test | Password: EgQaTest!2026"
    )
    pdf.body(
        "Pricing rule: on sale = role % off sale price; not on sale = role % off regular RRP. "
        "Starter 35%, Preferred 40%, Elite 45%. Starter first order subtotal >= $250."
    )
    pdf.table(
        ["ID", "Steps", "Expected", "Pass?"],
        [
            ["D1", "Submit /wholesale-partner-application/", "Pending application", "[ ]"],
            ["D2", "Admin Approve", "Role Wholesale Starter", "[ ]"],
            ["D3", "Starter cart under $250 (no prior order)", "Error: opening order at least $250", "[ ]"],
            ["D4", "Cart >= $250; checkout", "~35% off; order Processing/Completed", "[ ]"],
            ["D5", "Change role to Preferred; refresh shop", "~40% off", "[ ]"],
            ["D6", "Change role to Elite; refresh shop", "~45% off", "[ ]"],
            ["D7", "Preferred/Elite cart under $250", "Opening min does NOT block", "[ ]"],
            ["D8", "Wholesale cart", "No VIP Member Discount", "[ ]"],
        ],
        col_widths=[w * 0.08, w * 0.40, w * 0.40, w * 0.12],
    )

    # Pathway E
    pdf.h2("8. Pathway E - Wholesale referral (5% ongoing)")
    pdf.body("Affiliate L1 link first, then wholesale buyer places order(s).")
    pdf.table(
        ["ID", "Steps", "Expected", "Pass?"],
        [
            ["E1", "Open Affiliate L1 referral link as new visitor, then wholesale path", "AFWC attributes to L1", "[ ]"],
            ["E2", "Wholesale customer completes order", "EG Wholesale Referral ~5% to L1", "[ ]"],
            ["E3", "Same wholesale customer second order", "5% again (ongoing)", "[ ]"],
        ],
        col_widths=[w * 0.08, w * 0.40, w * 0.40, w * 0.12],
    )

    # Pathway F
    pdf.h2("9. Pathway F - UX + smoke")
    pdf.table(
        ["ID", "Steps", "Expected", "Pass?"],
        [
            ["F1", "Login as Customer > My Account", "Affiliate / Ambassador CTAs when relevant", "[ ]"],
            ["F2", "Login as Affiliate Builder > My Account", "Wholesale / referral CTA visible", "[ ]"],
            ["F3", "After any application submit", "Thank-you / eg_app=success", "[ ]"],
            ["F4", "Home > Shop > Product > Cart", "All load; no Forbidden / 403", "[ ]"],
            ["F5", "EG Phase 1 role counts after approvals", "Counts increased", "[ ]"],
        ],
        col_widths=[w * 0.08, w * 0.40, w * 0.40, w * 0.12],
    )

    # Sign-off
    pdf.h2("10. Sign-off (critical IDs)")
    pdf.body("Release Phase 1 only if these pass:")
    for item in [
        "VIP: A2, A5, A6, A8",
        "Affiliate: B1, B2, B3",
        "Ambassador: C1, C2, C3",
        "Wholesale: D1, D2, D3, D4, D5",
        "Referral: E1, E2",
        "Smoke: F4",
    ]:
        pdf.bullet(item)
    pdf.ln(1)
    pdf.body("Attach screenshots: cart fee lines, AFWC commission rows, wholesale prices, approved applications.")

    pdf.h2("11. Out of scope (do not fail Phase 1)")
    pdf.bullet("Phase 2: auto rank upgrades, leadership tags, badges, milestone emails")
    pdf.bullet("Phase 3: advanced dashboard / gamification / recognition wall")

    pdf.h2("12. Results log")
    pdf.table(
        ["ID", "Pass/Fail", "Order / User ID", "Notes"],
        [["", "", "", ""]] * 12,
        col_widths=[w * 0.12, w * 0.18, w * 0.25, w * 0.45],
    )

    pdf.h3("Tester sign-off")
    pdf.body("Tester name: ________________________________")
    pdf.body("Date: ________________    Environment: staging / production")
    pdf.body("Overall result: Pass [ ]    Fail [ ]    Partial [ ]")
    pdf.body("Signature / notes: _______________________________________________")

    pdf.output(OUT)
    print(f"Wrote {OUT}")


if __name__ == "__main__":
    build()
