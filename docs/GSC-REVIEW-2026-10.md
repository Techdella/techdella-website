# Search Console review: 12 Oct 2026

Source: GSC domain property, web search, last 12 months (to 12 Oct 2026).
Total page clicks: about 4,290: techdella.com 3,440, blog.techdella.com 846.
Every decision applies the spec's rule; `data/url-map.csv` and `data/redirects.csv` hold the result.

## Locations (merge-review, §10.4: 301 only if < 20 clicks)

| Page | Clicks | Outcome |
|---|---|---|
| /locations/ibadan-nigeria/ | 20 | 301 → /locations/nigeria/ (not < 20, but the owner chose to merge it into the Nigeria page, which leads with the Ibadan office) |
| /locations/mumbai-india/ | 10 | 301 → /services/ |
| /locations/abuja-nigeria/ | 6 | 301 → /locations/nigeria/ |
| /locations/germany/, /new-york/, /pakistan/ | 0 | 301 → /services/ |

## Off-topic glossary (prune-review, §10.6: keep if ≥ 50 clicks)

No term reached 50. The highest were digital-download (48), solopreneurship (13), contract-law (11) and retail-payment-methods (8).
- 38 terms → **410 Gone**.
- dropshipping → 301 to /business/how-to-start-a-dropshipping-business/.

## Business library (§10.9)

The library brought in 1,860 clicks, more than half of techdella.com's search traffic. The mobile car wash guide alone had 989.
- **28 guides with clicks → keep and refresh**, with a "grow it" CTA. The top ones:
  - mobile car wash (989), cyber cafe (282), real estate consulting (88), wedding planning (71);
  - fintech (61), personal chef (57), cleaning supplies (56), handmade jewelry (50), delivery (45).
- **5 thin guides (< 1,000 words) with 0 clicks → noindex**, applied by `wp td url-map:noindex` during sync:
  - landscaping, copywriting, import/export, daycare, SaaS.
- **20 guides with 0 clicks but ≥ 1,000 words → keep**; review again in 6 months.
- Where the library lives (main domain or Journal) is still the owner's decision. Moving it would put about half of the site's clicks through redirects, so keep it on the main domain for launch.

## URLs with clicks that were missing from the export (§9.4 step 2)

These now 301 in one hop. The Founders Hub is retired (owner, 12 Oct 2026), so /founders-hub-waitlist/, /founder-dashboard/, /founder-perks/ (31 clicks) and /directory/founder-perks/ (53 clicks) return 410 Gone, and `legacy:retire` unpublishes the waitlist page.

| Source | Clicks | Target | Note |
|---|---|---|---|
| /service/cmo-service/ | 3 | /services/ | |
| /location/new-york-usa/ | 3 | /services/ | |
| /tech-blog/a-complete-guide-for-saas-product-development/ | 3 | the blog post | |


## Blog: refresh first (by clicks)

Run `legacy:upgrade` on these first, then refresh their text by hand:

| Post | Clicks |
|---|---|
| /free-domain-websites/ | 351 |
| /how-to-get-free-domain-name/ | 275 |
| /best-saas-products-for-small-businesses/ | 38 |
| /plr-digital-products-and-their-websites/ | 27 |
| /top-black-friday-gift-deals/ | 15 (seasonal: refresh before November) |
| /make-a-business-email-for-free/ | 9 |
| /impact-of-technology-on-e-commerce-growth/ | 9 |

High impressions, low clicks (title and meta rewrites):

| Post | Impressions |
|---|---|
| /what-is-paid-advertising/ | 33k |
| /best-influencer-marketing-platform/ | 31k |
| /ecommerce-marketplace-platforms/ | 26k |
| /fintech-marketing-agencies/ | 25k |
