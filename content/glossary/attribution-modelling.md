---
title: "What Is Attribution Modeling? Models Compared With Examples"
slug: attribution-modelling
excerpt: "Attribution modeling is the rule for splitting conversion credit across touchpoints. Here is how each model works, one deal run through five models, and what GA4 and Google Ads support now."
primary_kw: attribution modeling
secondary_kws: [attribution modelling, data-driven attribution, last click attribution, multi-touch attribution models]
intent: informational
audience: "Marketers reading attribution reports in GA4, Google Ads or a CRM"
short_definition: "Attribution modeling (also spelled attribution modelling) is the set of rules or algorithms that decide how credit for a conversion is shared among the touchpoints a customer interacted with, such as ads, emails and searches, before converting."
taxonomies:
  glossary_category: [analytics]
related_terms: [marketing-attribution, customer-acquisition-cost, remarketing, pay-per-click-advertising, seo-analytics]
related_service: paid-ads
author: joanna-okedara-kalu
reviewer: chinedu-emmanuel-kalu
internal_links:
  - /services/paid-ads/
  - /glossary/marketing-attribution/
  - /glossary/customer-acquisition-cost/
  - /glossary/remarketing/
rank_math_description: "Attribution modeling explained: first click, last click, linear, position-based, time decay and data-driven models, with one deal worked through each."
---

Attribution modeling is the rule that decides which touchpoints get credit for a conversion. If a buyer clicked a LinkedIn ad, later read your blog, opened a webinar email and finally booked a call from a Google search, the model decides whether the search gets all the credit, the ad does, or everyone gets a share. Change the model and the same data tells a different story about which channels work.

:::takeaways
- Single-touch models give 100% of the credit to one touchpoint; multi-touch models share it.
- Google Ads no longer supports first click, linear, time decay or position-based models. GA4's attribution reports offer data-driven and last-click options.
- No model shows cause and effect. Each is a lens, and you should know which one your report uses.
- Compare models side by side to see which channels start journeys and which close them.
:::

## The main attribution models

| Model | How credit is split | Tends to favor |
|---|---|---|
| First click | 100% to the first touchpoint | Channels that create awareness |
| Last click | 100% to the last touchpoint before converting | Channels that capture demand, such as branded search |
| Linear | Equal share to every touchpoint | Long journeys with many touches |
| Position-based (U-shaped) | Commonly 40% first, 40% last, 20% shared across the middle | Both opening and closing channels |
| Time decay | More credit the closer a touch is to the conversion | Late-stage channels |
| Data-driven | An algorithm compares converting and non-converting paths to estimate each touch's contribution | Depends on your data; needs volume |

## One deal, five models

An illustrative $12,000 deal with four touches over 40 days: a LinkedIn ad (day 0), an organic blog visit (day 20), a webinar email (day 35) and a branded Google search (day 40). Time decay uses a seven-day half-life.

| Touchpoint | First click | Last click | Linear | Position-based | Time decay |
|---|---|---|---|---|---|
| LinkedIn ad | $12,000 | $0 | $3,000 | $4,800 | $130 |
| Organic blog | $0 | $0 | $3,000 | $1,200 | $940 |
| Webinar email | $0 | $0 | $3,000 | $1,200 | $4,140 |
| Branded search | $0 | $12,000 | $3,000 | $4,800 | $6,790 |

Under last click, LinkedIn looks worthless. Under first click, it looks like the only thing that worked. The truth is probably in between, which is why a single model shouldn't decide budgets alone.

## What Google's tools support now

[Google Ads](https://support.google.com/google-ads/answer/6259715) states that the first click, linear, time decay and position-based models are no longer supported, and conversion actions that used them were upgraded to data-driven attribution. In [Google Analytics 4](https://support.google.com/analytics/answer/10596866), the Attribution reports offer three models: data-driven, paid and organic last click, and Google paid channels last click. GA4 also excludes direct visits from attribution credit unless the whole path is direct.

Need a rules-based view for comparison? Calculate it from raw path data, or use a CRM that still offers it.

:::myth-fact
Myth: Data-driven attribution shows which channel caused the sale.
Fact: It's better at weighting touchpoints than fixed rules, but it still only sees tracked touches and finds patterns, not proof. Use holdout tests for causal questions.
Myth: Choosing the right model fixes attribution.
Fact: Gaps in tracking matter more than the model. Missing UTM tags, lost consent and untracked word of mouth distort every model the same way.
:::

## Example from our work

:::quote "Joanna Okedara-Kalu" "Founder & CEO, TechDella"
I care less about which model a team picks than whether their tracking is honest. Before we argue over first touch versus data-driven, we check UTM tagging, the consent setup and whether the CRM records how a deal really started. Then we ask new customers where they heard of us. Self-reported answers are messy, but they surface the podcasts and referrals no model can see.
:::

## How to use attribution modeling well

:::steps "Make models useful, not confusing"
1. Pick a default and write it down | Choose one model for routine reporting, usually data-driven or last click, and label every report with it.
2. Compare models | In GA4, use the Attribution models report to see which channels gain credit under each model. Big swings show a channel's role.
3. Separate roles | Judge awareness channels on assisted conversions and new audience reach, and closing channels on last-click results.
4. Check the lookback window | Make sure it covers your typical sales cycle, or early touches will drop out.
5. Close the loop with revenue | Import offline conversions or CRM stages so models optimize toward deals, not just form fills.
:::

:::cta "Not sure your ad reports are giving the full picture?" "See our paid ads service" /services/paid-ads/
We set up conversion tracking and attribution so budgets follow revenue, not just last clicks.
:::

Attribution models are one tool inside [marketing attribution](/glossary/marketing-attribution/) as a whole, which also includes self-reported data and incrementality tests. The channel credit they produce feeds channel [customer acquisition cost](/glossary/customer-acquisition-cost/). [Remarketing](/glossary/remarketing/) tends to look strong under last-click models, so check its role under other views too.

## Related terms

- [Marketing attribution](/glossary/marketing-attribution/): the wider practice beyond click models.
- [Customer acquisition cost](/glossary/customer-acquisition-cost/): channel CAC depends on the model you choose.
- [Remarketing](/glossary/remarketing/): often over-credited by last-click models.
- [Pay-per-click advertising](/glossary/pay-per-click-advertising/): where model choice affects automated bidding.

:::faq
### What is the difference between single-touch and multi-touch attribution?
Single-touch models, first click and last click, give all the credit to one interaction. Multi-touch models, such as linear, position-based, time decay and data-driven, share credit across several. Multi-touch views are closer to how B2B buyers behave, but they're harder to set up and explain.
### Which attribution model should I use?
For Google Ads and GA4, data-driven is the default and works well with enough conversion volume. With low volume, last click is simpler and stable. Whatever you choose, compare models periodically to understand each channel's role.
### Why did my attribution model change in Google Ads?
Google retired the first click, linear, time decay and position-based models. Conversion actions using them were upgraded to data-driven attribution, which can shift reported conversions between campaigns even though actual results didn't change.
### Is attribution modelling the same as attribution modeling?
Yes. "Modelling" is the British spelling and "modeling" the American one. The concept, models and tools are identical.
:::

## How TechDella can help

Our [paid ads service](/services/paid-ads/) includes conversion tracking, CRM imports and attribution reviews, so campaign decisions rest on more than one model.
