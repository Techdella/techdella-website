---
title: "What is Performance Max? Google Ads PMax Explained"
slug: performance-max
excerpt: "Performance Max is Google Ads' goal-based campaign type that runs across Search, YouTube, Display, Discover, Gmail and Maps from one campaign. Here is how it works for B2B."
primary_kw: performance max
secondary_kws: [pmax, performance max campaigns, performance max asset groups, performance max b2b]
intent: informational
audience: "B2B marketing leads running Google Ads"
short_definition: "Performance Max is a goal-based Google Ads campaign type that uses Google AI to serve ads across all Google Ads inventory, including Search, YouTube, Display, Discover, Gmail and Maps, from one campaign."
taxonomies:
  glossary_category: [paid-ads]
  td_service_tag: [paid-ads]
related_terms: [pay-per-click-advertising, remarketing, consent-mode-v2, server-side-tracking, advantage-plus]
related_service: paid-ads
internal_links:
  - /services/paid-ads/
  - /glossary/pay-per-click-advertising/
  - /glossary/consent-mode-v2/
  - /glossary/lead-scoring/
author: joanna-okedara-kalu
reviewer: chinedu-emmanuel-kalu
rank_math_description: "Performance Max explained: how PMax asset groups and audience signals work, the exact asset limits, a B2B lead-gen example, and the setup checklist we use."
---

Performance Max (PMax) is a Google Ads campaign type that lets one campaign serve ads across all of Google's inventory, including YouTube, Display, Search, Discover, Gmail and Maps, and uses Google AI to decide where, when and with which creative ([Google Ads Help](https://support.google.com/google-ads/answer/10724817)). You supply a goal, a budget, creative assets and signals. Google handles bidding, placement and ad assembly.

## Why Performance Max matters

For many advertisers, PMax is now the default way to reach Google's non-search inventory. It also competes in Search auctions alongside your Search campaigns. That makes it powerful and risky in equal measure.

:::callout key "The trade-off in one sentence"
Performance Max trades control for reach: it optimizes toward exactly the conversion you give it, so it is only as good as your conversion tracking and the signals you feed it.
:::

For B2B lead generation this matters a lot. If "conversion" means any form fill, PMax will find the cheapest form fills, which are often low-quality leads. If it means a qualified lead imported from your CRM, it optimizes toward pipeline.

## How Performance Max works

A PMax campaign is built from asset groups. Google describes an asset group as a collection of creatives centered on a theme or audience; Google AI combines those assets into ads for each placement.

| Asset (per asset group) | Limit | Required? |
|---|---|---|
| Headlines | 3–15, 30 characters max | Yes |
| Long headlines | 1–5, 90 characters max | Yes |
| Descriptions | 2–5, 90 characters max | Yes |
| Business name | 25 characters max | Yes |
| Landscape (1.91:1) and square (1:1) images | Up to 20 each | Yes |
| Square logo | Up to 5 | Yes |
| Videos (16:9, 1:1, 9:16) | Up to 15 in total | No, but Google may auto-generate one if none is added |

Source: [Performance Max specs, Google Ads Help](https://support.google.com/google-ads/answer/17091269). Limits change; check the page before you build.

Around the assets you add **audience signals** (your customer lists, website visitors, custom segments) that guide Google's learning, and you decide whether to allow **final URL expansion**, which lets Google send traffic to a more relevant landing page than the one you set.

## Example: PMax for a B2B SaaS lead campaign

A hypothetical HR software company sets a PMax budget of $3,000 a month.

- **Version A** optimizes for "Demo form submitted". It gets 60 conversions at $50 each. Sales accepts 9 as real opportunities: $3,000 ÷ 9 = **$333 per sales-qualified lead**.
- **Version B** imports CRM stages and optimizes for "Sales-qualified lead". Volume drops to 25 form fills at $120 each, but sales accepts 14: $3,000 ÷ 14 = **$214 per sales-qualified lead**.

The cheaper cost per conversion was the more expensive campaign. This is why [lead scoring](/glossary/lead-scoring/) and offline conversion imports matter before you scale PMax.

:::callout note "From our work"
[CONFIRM: Joanna — add one first-hand example here: what we saw with a client (with permission), a screenshot, or our own number.]
:::

## How to set up Performance Max

:::checklist "Before you launch PMax"
- Set the conversion goal to a qualified action, not every form fill
- Import offline conversions from your CRM so Google learns what a good lead looks like
- Implement [Consent Mode v2](/glossary/consent-mode-v2/) if you serve users in the EEA or UK
- Build one asset group per theme: by service, product line or audience
- Fill every asset slot with your own copy, images and videos in all three orientations
- Add audience signals: customer lists, site visitors and custom segments from competitor or topic terms
- Decide on final URL expansion and exclude careers, blog and support pages if you allow it
- Add brand exclusions if you don't want PMax buying your own brand searches
- Give it time: judge results on qualified leads over weeks, not clicks over days
:::

:::myth-fact
Myth: Performance Max replaces your Search campaigns.
Fact: Many accounts run both. Search keeps tight control over priority queries; PMax extends reach across YouTube, Display, Gmail, Discover and Maps. Watch for overlap in Search terms reports.
:::

:::faq
### Is Performance Max good for B2B?
It can be, if you optimize for qualified leads rather than raw form fills and you have enough conversion volume for the system to learn. With low volume or weak tracking, a well-structured Search campaign is usually the safer start.
### What are asset groups in Performance Max?
Asset groups are collections of headlines, descriptions, images, logos and videos built around one theme or audience. Google mixes them into ads for each placement. Most accounts use one asset group per service or audience.
### Can I see where Performance Max ads run?
Reporting has improved, with channel and placement views and search term insights, but it remains less granular than separate Search, YouTube and Display campaigns. Check the reports in Google Ads and compare with your CRM.
### How long does Performance Max take to learn?
Google advises allowing a learning period after launch and after big changes. In practice, judge a campaign after several weeks and enough conversions, not after a few days.
:::

:::cta "Want PMax to bring pipeline, not just leads?" "See paid ads" /services/paid-ads/
We connect your CRM to Google Ads so campaigns optimize for leads sales actually wants.
:::
