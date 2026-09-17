# GoAffPro → Site Affiliate (AFWC) Cutover SOP

Goal: stop using GoAffPro and run all affiliates on the site's own system
(**Affiliate for WooCommerce** + **Earth Goddess Phase 1**).

Admin tool: **WP Admin → EG Phase 1 → GoAffPro Import**

---

## 0. Before you start

1. Back up the database (hosting backup or `wp db export`).
2. Confirm active plugins: WooCommerce, Affiliate for WooCommerce, WPLoyalty.
3. Open **EG Phase 1** once so the Phase 1 seeder has run (commission plans + tags exist).
4. Keep GoAffPro **active** for now.

---

## 1. Dry run the import

1. Go to **EG Phase 1 → GoAffPro Import**.
2. Upload the GoAffPro export CSV.
3. Leave **Dry run** ticked.
4. Click **Start import**, then **Continue import** until it says *Finished*.
5. Read the report:

| Counter | Meaning |
|---------|---------|
| Would import | Rows that will become affiliates |
| Would create new WP user | Emails with no account yet |
| Matched existing WP user | Emails that already have an account |
| Referral code applied | Old `?ref=CODE` will keep working |
| Referral code rejected by AFWC pattern | Code has a dash / starts with a number — affiliate gets the default link |
| Referral code already used | Two affiliates want the same code; fix manually later |
| Skipped (status not approved) | suspended / blocked / invited rows |

Fix anything obviously wrong in the CSV and re-upload if needed.

---

## 2. Live import

1. Upload the same CSV again with **Dry run unticked**.
2. Click **Start import** → **Continue import** until *Finished*.

What each imported affiliate gets:

- Role **Affiliate Business Builder** added (existing roles kept)
- `afwc_is_affiliate = yes` and affiliate-since date
- AFWC tag **Clear Quartz Partner** (default rank — retag later per SOP)
- PayPal email as payout email when present
- Old referral code as their AFWC URL identifier when valid

New accounts are created with a random password and **no email is sent**.
Affiliates use **Lost your password** on `/my-account/` for first login.

---

## 3. Build the genealogy manually

The importer never assigns parents.

1. Click **Download parent checklist CSV**.
2. Open it — it is sorted by parent, with WP user IDs filled in.
3. For each child row:
   - WP Admin → **Affiliates** (Affiliate for WooCommerce) → open the **child** affiliate
   - Set **Parent affiliate** to the parent from the checklist
   - Save
   - Mark `Linked in AFWC` = `yes` in your copy of the CSV
4. Rows where the parent shows `not found` mean the parent email has no WP user
   (usually a suspended/blocked GoAffPro account) — leave those with no parent.

Work top-down: link the biggest uplines first (they appear most often), then their children.

---

## 4. Verify before switching off GoAffPro

1. Pick an imported affiliate with a valid code.
2. In Incognito open `https://earthgoddess.com.au/?ref=THEIRCODE`.
3. Place a small test order as a different customer.
4. WP Admin → **Affiliates** → confirm the commission appears for that affiliate.
5. If they have a parent linked, confirm the parent's tier commission also appears.
6. Log in as that affiliate → **My Account → Affiliate** shows their dashboard and link.

---

## 5. Switch off GoAffPro

Only after step 4 passes:

1. WP Admin → **Plugins** → deactivate **GoAffPro**.
2. Remove GoAffPro links/banners from menus, footer, and any landing pages.
3. Purge cache: `wp litespeed-purge all`.
4. Re-check: home, shop, product, cart, `/my-account/` (no 403, no broken widgets).

---

## 6. Tell the affiliates

Message to send:

> Our affiliate program now runs directly on earthgoddess.com.au.
> Log in at `/my-account/` (use "Lost your password" if it is your first time),
> then open the **Affiliate** tab for your dashboard and referral link.
> Your existing referral code still works where possible.

---

## After cutover

- New affiliates apply at `/affiliate-business-builder-application/` and are approved in **EG Applications**
- Ranks (Amethyst / Green Aventurine / Moonstone) are retagged manually per `SOP-CHECKLIST.md`
- Commissions and multi-tier splits come from the seeded **EG …** AFWC plans

## Not migrated

- Historical GoAffPro commission balances and payout history
- GoAffPro coupons, tags, plans, and downline stats (parents are rebuilt manually)
