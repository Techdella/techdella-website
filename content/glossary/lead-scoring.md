---
title: "What is Lead Scoring? Models, Examples and Thresholds"
slug: lead-scoring
excerpt: "Lead scoring ranks leads by how well they fit your ideal customer and how engaged they are, so sales calls the right people first. Here is a simple model you can copy."
primary_kw: lead scoring
secondary_kws: [lead scoring model, b2b lead scoring, fit and engagement score, predictive lead scoring]
intent: informational
audience: "B2B founders, marketing and sales leads"
short_definition: "Lead scoring is a method of ranking leads by assigning points for how well they fit your ideal customer profile and how engaged they are, so sales and marketing can prioritize who to contact and when."
taxonomies:
  glossary_category: [lead-generation]
  td_service_tag: [lead-generation]
related_terms: [mql-vs-sql, intent-data, lead-generation, marketing-automation, account-based-marketing]
related_service: lead-generation
internal_links:
  - /services/lead-generation/
  - /glossary/mql-vs-sql/
  - /glossary/intent-data/
  - /services/marketing-automation/
author: joanna-okedara-kalu
reviewer: chinedu-emmanuel-kalu
rank_math_description: "Lead scoring explained: fit vs engagement scores, a copyable B2B scoring model with point values, thresholds for MQL handoff, decay, and how to test the model."
---

Lead scoring is a way to rank your leads by giving each one points: points for **fit** (how closely they match your ideal customer) and points for **engagement** (what they've done, like visiting pricing or requesting a demo). When a lead passes a threshold, it moves to the next stage, usually from marketing to sales. The aim is simple: your sales team spends its time on the leads most likely to buy.

## Why lead scoring matters

Without lead scoring, sales either calls everyone, which wastes hours on students and competitors, or calls no one, and good leads go cold. Scoring makes the handoff between marketing and sales explicit.

:::callout tip "Why fit and engagement are separate"
HubSpot's lead scoring tool, for example, separates fit scores, built from properties like company size or region, from engagement scores, built from actions like email opens or form submissions, and can combine them into a grid from A1 to C3 ([HubSpot Knowledge Base](https://knowledge.hubspot.com/scoring/understand-the-lead-scoring-tool)). A perfect-fit lead who has done nothing and a highly active lead who will never buy need different treatment.
:::

Scoring also feeds your ad platforms. If you send "qualified" events back to Google or Meta, they optimize toward better leads, not just more of them.

## How lead scoring works

A lead scoring model has two halves. Here is a simple B2B version for a company selling to SaaS firms with 20–500 employees.

| Fit criteria | Points | Engagement criteria | Points |
|---|---|---|---|
| Company size 20–500 | +20 | Requested a demo | +40 |
| Industry: SaaS or fintech | +15 | Visited pricing page (last 30 days) | +15 |
| Job title: Head of, VP, C-level | +15 | Opened 3+ emails (last 30 days) | +5 |
| Target country | +10 | Downloaded a guide | +10 |
| Free email domain (gmail.com) | −15 | Visited careers page | −10 |
| Competitor or student | −50 | No activity for 60 days | −20 (decay) |

Set a threshold based on history, for example **fit ≥ 40 and total ≥ 70** to become a marketing-qualified lead. The [MQL vs SQL](/glossary/mql-vs-sql/) definitions should be agreed with sales before you switch it on.

## Example: scoring three leads

- **Lead A:** Head of Growth at a 120-person SaaS company in the US, visited pricing and requested a demo. Fit 20 + 15 + 15 + 10 = 60. Engagement 40 + 15 = 55. **Total 115: route to sales today.**
- **Lead B:** Marketing manager at a 5-person agency, downloaded two guides, opened six emails. Fit 0. Engagement 10 + 10 + 5 = 25. **Total 25: keep nurturing.**
- **Lead C:** VP Finance at a 300-person fintech, visited pricing once, using a Gmail address. Fit 20 + 15 + 15 + 10 − 15 = 45. Engagement 15. **Total 60: high fit, low engagement, so a good target for a personal email or a [LinkedIn Thought Leader Ad](/glossary/linkedin-thought-leader-ads/) sequence.**

:::callout note "From our work"
[CONFIRM: Joanna — add one first-hand example here: what we saw with a client (with permission), a screenshot, or our own number.]
:::

## How to build a lead scoring model

:::steps
1. Look at closed deals | Pull the last 20–50 won and lost deals. What did winners have in common: size, role, industry, actions?
2. Define fit | Turn the pattern into 4–6 fit criteria with points, including negatives for clear non-buyers.
3. Define engagement | Weight high-intent actions (demo, pricing, contact) far above low-intent ones (opens, blog visits).
4. Add decay | Reduce engagement points as activity ages, so last year's webinar doesn't keep a lead "hot".
5. Agree the threshold with sales | Pick the score that routes a lead to sales and the service level for follow-up.
6. Automate the routing | Use [marketing automation](/services/marketing-automation/) to assign owners, alert sales and start nurture sequences.
7. Review quarterly | Compare scores with outcomes. If high scorers don't convert, change the weights.
:::

:::myth-fact
Myth: More points for more activity always means a better lead.
Fact: Activity without fit is noise. A student who reads every blog post can outscore a buyer who visited pricing once. Separate fit from engagement and penalize clear non-buyers.
:::

Add [intent data](/glossary/intent-data/) as an extra engagement signal once your basic model works.

:::faq
### What is a good lead score threshold?
There's no universal number. Set it by looking at past leads that became customers and finding the score most of them reached. Start conservatively, then adjust after reviewing results with sales each quarter.
### Should I use predictive lead scoring?
Predictive scoring uses machine learning on your CRM history. It needs enough closed deals to learn from. Most small B2B teams should start with a manual model they understand, then test predictive scoring as data grows.
### What is lead score decay?
Decay reduces engagement points as actions get older, for example halving them every few months. It keeps scores reflecting current interest, so sales isn't chasing people who were active a year ago.
### Who should own lead scoring?
Marketing usually builds and maintains the model, but sales must agree the criteria and threshold. Review it together, or the scores won't be trusted.
:::

:::cta "Want sales to call the right leads first?" "See lead generation" /services/lead-generation/
We build the scoring model, route leads automatically and feed quality signals back to your ads.
:::
