# Earth Goddess AU – Phase 1 Manual Test Plan

**Environment:** https://staging.earthgoddess.com.au/  
**Admin:** WP Admin → EG Phase 1 / EG Applications / Users / WooCommerce / Affiliates  
**Payment:** Use any method that reaches **Processing** or **Completed**  
**Log columns:** `ID | Steps | Expected | Actual | Pass/Fail | Order or User ID | Screenshot`

Use 2 browsers (or Normal + Incognito) so affiliate cookies and roles do not collide.

---

## 0. Prep (once)

1. Log into WP Admin.
2. Open **EG Phase 1** — install status should be OK (re-run seeder only if something is MISSING).
3. Purge **LiteSpeed** cache.
4. Prepare accounts:

| Account | How to set up |
|---------|----------------|
| VIP tester | Users → add/edit → role **VIP Shopper** (or Customer) |
| Buyer | Customer role |
| Affiliate L1 | Submit affiliate application (Test B) |
| Ambassador | Submit ambassador application (Test C) |
| Wholesale Starter | Submit wholesale application (Test D) |

5. Open a results sheet and copy the IDs below.

---

## A. VIP Shopper (loyalty + member %)

| ID | Steps | Expected | Pass? |
|----|--------|----------|-------|
| A1 | Register new customer (or use VIP). Check WPLoyalty / points balance | **+50** points for signup (if campaign active) | |
| A2 | Login as VIP. Annual spend still under $500. Add ~$50 product → Cart/Checkout | Fee **VIP Member Discount (10%)** | |
| A3 | Complete the order | Points ≈ **1 point per $1** | |
| A4 | Submit a product review (if reviews enabled) | **+50** points | |
| A5 | Increase trailing 365-day spend to **$500–$999** (more orders, or temporarily edit order totals). Refresh cart | Discount becomes **12%** | |
| A6 | Raise spend to **≥ $1,000**. Refresh cart | Discount becomes **15%** | |
| A7 | Redeem a loyalty reward/coupon from My Account / loyalty UI | Reward applies; points decrease | |
| A8 | Login as a **Wholesale** user. Add product to cart | **No** VIP Member Discount fee | |

**Fail if:** wrong %, VIP stacks with wholesale, or points never award.

---

## B. Affiliate Business Builder

| ID | Steps | Expected | Pass? |
|----|--------|----------|-------|
| B1 | Open `/affiliate-business-builder-application/`. Fill all fields. Submit | Success message (`eg_app=success`). **EG Applications** shows pending | |
| B2 | Admin → EG Applications → open row → check **Approve** → Update | Role **Affiliate Business Builder**; tag **Clear Quartz Partner**; affiliate area visible in My Account | |
| B3 | Copy L1 referral/affiliate link. Incognito as Buyer: open link → buy product → complete order | L1 earns ~**15%** (AFWC unpaid commission / Clear Quartz plan) | |
| B4 | Admin retags L1 → **Amethyst Partner**. Create child affiliate L2 under L1 (AFWC network/invite). Buyer purchases via **L2** link | L2 gets personal commission; L1 gets parent ~**3%** | |
| B5 | Optional: retag to Green Aventurine / Moonstone; sell via deepest child | Parent splits match **5\|3** or **5\|4\|2** | |
| B6 | SOP check only | Active Partner = ≥ **$100** personal SV in last 30 days. **No auto-upgrade** | |

**Fail if:** no commission, wrong plan, or Ambassador tags get Builder rates.

---

## C. Ambassador (no downline)

| ID | Steps | Expected | Pass? |
|----|--------|----------|-------|
| C1 | Open `/ambassador-application/`. Submit all fields | Pending in EG Applications | |
| C2 | Approve application | Role **Ambassador**; tag **Seed Ambassador** | |
| C3 | Buyer purchases via ambassador referral link | Commission **20%** | |
| C4 | Retag → **Bloom Ambassador**, new referred order | Commission **25%** | |
| C5 | Retag → **Goddess Ambassador**, new referred order | Commission **30%** | |
| C6 | If a child can be linked under ambassador | **No parent commission** on Ambassador plans | |

**Fail if:** upline earns on Seed/Bloom/Goddess Ambassador sales.

---

## D. Wholesale Partner

| ID | Steps | Expected | Pass? |
|----|--------|----------|-------|
| D1 | Open `/wholesale-partner-application/`. Submit | Pending application | |
| D2 | Approve | Role **Wholesale Starter** | |
| D3 | Login as Starter with **no prior completed order**. Cart/checkout **under $250** | Error: opening order must be at least **$250** | |
| D4 | Cart **≥ $250**. Check product/cart prices vs guest RRP | About **35% off RRP** | |
| D5 | Admin changes role → **Wholesale Preferred**. Refresh shop | About **40% off RRP** | |
| D6 | Admin changes role → **Wholesale Elite**. Refresh shop | About **45% off RRP** | |
| D7 | As Preferred/Elite, try cart under $250 | Opening minimum does **not** block | |
| D8 | Wholesale cart | No VIP Member Discount fee | |

**Fail if:** wrong discount, min blocks wrong roles, or VIP fee appears.

---

## E. Wholesale referral (5% ongoing)

| ID | Steps | Expected | Pass? |
|----|--------|----------|-------|
| E1 | As Affiliate L1, copy referral link. New visitor clicks link, then becomes wholesale / places wholesale order | AFWC attributes order to L1 | |
| E2 | Wholesale customer completes order | Plan **EG Wholesale Referral 5%** → L1 ~**5%** | |
| E3 | Same wholesale customer places a second order | Commission again (ongoing, not one-time) | |

**Fail if:** 5% never fires for wholesale-role buyers.

---

## F. UX + smoke

| ID | Steps | Expected | Pass? |
|----|--------|----------|-------|
| F1 | Login as Customer → My Account | Pathway CTAs (Affiliate / Ambassador) when relevant | |
| F2 | Login as Affiliate Builder → My Account | Wholesale / referral CTA visible | |
| F3 | After any application submit | Thank-you / `eg_app=success` | |
| F4 | Browse Home → Shop → Product → Cart | All load; **no Forbidden / 403** | |
| F5 | EG Phase 1 role counts after approvals | Counts increased | |

---

## Sign-off

**Release Phase 1** only if these critical IDs pass:

- VIP: **A2, A5, A6, A8**
- Affiliate: **B1, B2, B3**
- Ambassador: **C1, C2, C3**
- Wholesale: **D1, D2, D3, D4, D5**
- Referral: **E1, E2**
- Smoke: **F4**

Attach screenshots: cart fee lines, AFWC commission rows, wholesale prices, approved applications.

---

## Out of scope (do not fail Phase 1 for these)

- Auto rank upgrades, auto leadership tags, badges, milestone emails (Phase 2)
- Advanced dashboard / gamification / recognition wall (Phase 3)
