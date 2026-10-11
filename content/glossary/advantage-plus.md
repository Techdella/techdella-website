---
title: "What is Meta Advantage+? Campaigns and Settings Explained"
slug: advantage-plus
excerpt: "Meta Advantage+ is Meta's set of AI-automated campaign types and settings for Facebook and Instagram ads. Here is what it automates, what you still control, and when to use it."
primary_kw: meta advantage+
secondary_kws: [advantage plus, advantage+ sales campaigns, advantage+ audience, advantage+ leads campaigns]
intent: informational
audience: "B2B and ecommerce marketing leads running Meta ads"
short_definition: "Meta Advantage+ is Meta's portfolio of AI-automated ad products for Facebook and Instagram, including Advantage+ sales, app and leads campaigns and settings like Advantage+ audience, placements and creative."
taxonomies:
  glossary_category: [paid-ads]
  td_service_tag: [paid-ads]
related_terms: [meta-conversions-api, performance-max, remarketing, lead-scoring]
related_service: paid-ads
internal_links:
  - /services/paid-ads/
  - /glossary/meta-conversions-api/
  - /glossary/performance-max/
  - /glossary/lead-scoring/
author: joanna-okedara-kalu
reviewer: chinedu-emmanuel-kalu
rank_math_description: "Meta Advantage+ explained: what Advantage+ campaigns automate, which controls you keep, a B2B lead quality example, and a setup checklist for Meta ads."
---

Meta Advantage+ is the name Meta gives to its AI-automated advertising products for Facebook, Instagram, Messenger and the Audience Network. It covers whole campaign types, such as Advantage+ sales, app and leads campaigns, and individual settings, such as Advantage+ audience, Advantage+ placements and Advantage+ creative. In each case, you give Meta a goal and inputs, and its systems make more of the targeting, placement, budget and creative decisions ([Meta Business Help Center](https://www.facebook.com/business/help/733979527611858)).

## Why Meta Advantage+ matters

Meta has steadily moved its defaults toward automation, much as Google did with [Performance Max](/glossary/performance-max/). Many settings now start in Advantage+ mode unless you switch them off, and Advantage+ shopping campaigns were renamed Advantage+ sales campaigns as the approach spread across objectives ([Meta](https://www.facebook.com/business/ads/meta-advantage-plus/sales-campaigns)).

:::callout key "What automation can't fix"
Advantage+ optimizes toward the events you send Meta. If those events are incomplete or low quality, automation scales the wrong outcome faster. Meta itself recommends connecting the Pixel and the [Conversions API](/glossary/meta-conversions-api/) for this reason.
:::

## How Advantage+ works

Think of Advantage+ as a set of dials you hand to Meta. Some are full campaign types; others are switches inside a manual campaign.

| Advantage+ product | What Meta automates | What you still decide |
|---|---|---|
| Advantage+ sales campaigns | Audience, placements, budget allocation and creative combinations for purchases | Budget, conversion event, creative supplied, country, exclusions |
| Advantage+ leads campaigns | The same levers, optimized for leads via forms or your website | Lead form or site, conversion event, creative |
| Advantage+ app campaigns | Delivery for app installs and in-app events | App events, budget, creative |
| Advantage+ audience | Who sees the ad, using your inputs as suggestions | Hard controls such as location, minimum age and excluded audiences |
| Advantage+ placements | Where the ad appears across Meta's apps | Placement exclusions where allowed |
| Advantage+ creative | Small enhancements: cropping, text variations, music, and similar | Which enhancements to allow |

The important distinction is between **suggestions** and **controls**. With Advantage+ audience, your interests and lookalikes become suggestions Meta can go beyond. Location, minimum age and exclusions remain firm limits.

## Example: Advantage+ leads for a B2B service

A hypothetical accounting firm runs Advantage+ leads campaigns with a $2,000 monthly budget and an instant form.

- **Month 1:** 160 leads at $12.50 each. Sales reaches 40 and qualifies 6. That's $2,000 ÷ 6 = **$333 per qualified lead**.
- **Month 2:** The team adds a qualifying question to the form, switches to the "higher intent" form type, and sends CRM stage updates back to Meta through the [Conversions API](/glossary/meta-conversions-api/). Leads drop to 70 at $28.57 each, but 12 qualify: $2,000 ÷ 12 = **$167 per qualified lead**.

Automation didn't change. The signal did. That's why our [paid ads](/services/paid-ads/) work starts with measurement.

:::quote "Joanna Okedara-Kalu" "Founder & CEO, TechDella"
I don't fight Meta's automation; I feed it better information. When a lead form is too easy, you get volume that sales never calls back. Add one honest qualifying question and send your CRM outcomes back to Meta, and the system learns what a real buyer looks like. Judge the campaign on cost per qualified lead, not cost per lead, or you'll reward the wrong thing.
:::

## How to use Meta Advantage+ well

:::steps
1. Fix measurement first | Install the Pixel and Conversions API with deduplication, and confirm events in Events Manager.
2. Choose the right event | Optimize for the deepest event with enough volume: purchases, qualified leads or booked calls, not page views.
3. Set the hard controls | Location, minimum age, excluded customer lists and any brand-safety exclusions.
4. Feed creative variety | Several formats and angles: static, video, carousel, short vertical video. Automation needs options to test.
5. Decide on enhancements | Review Advantage+ creative options and turn off any that conflict with brand rules.
6. Judge on business outcomes | Compare cost per qualified lead or ROAS against a manual campaign, using [lead scoring](/glossary/lead-scoring/) or CRM data.
:::

:::myth-fact
Myth: Advantage+ means you can't target anyone specific anymore.
Fact: Location, minimum age and excluded audiences stay as hard controls. What changes is that interests and lookalikes become suggestions Meta may expand beyond when it predicts better results.
:::

Advantage+ is Meta's counterpart to Google's [Performance Max](/glossary/performance-max/): both reward clean conversion data more than clever targeting.

:::faq
### Is Advantage+ good for B2B?
It can work, especially Advantage+ leads campaigns, if you optimize for qualified leads rather than raw form fills. Narrow B2B audiences with small budgets may still do better with manual campaigns and tighter targeting. Test both.
### What happened to Advantage+ shopping campaigns?
Meta renamed them Advantage+ sales campaigns. The same automation now applies to sales objectives more broadly, with catalog support across sales campaigns.
### Can I turn Advantage+ off?
Many Advantage+ settings can be switched off at campaign or ad set level, though Meta increasingly starts new campaigns with automation on. Check each setting during setup.
### Do I need the Conversions API for Advantage+?
It is not mandatory, but Meta recommends it. Browser-only tracking misses events lost to ad blockers and cookie limits, and automation optimizes on whatever data it receives.
:::

:::cta "Want Meta ads that bring qualified leads?" "See paid ads" /services/paid-ads/
We set up clean signals, then let automation work on the outcome you actually want.
:::
