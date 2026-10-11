---
title: "What Is A/B Testing? How to Run a Valid Test"
slug: a-b-testing
excerpt: "A/B testing compares two versions of a page, ad or email with real traffic to see which performs better. Here is how to run one properly, and how much traffic you need."
primary_kw: a/b testing
secondary_kws: [split testing, ab test sample size, statistical significance, experiment design]
intent: informational
audience: "Marketers who want to test pages, ads or emails and trust the results"
short_definition: "A/B testing is a controlled experiment that shows two versions of a page, ad, email or feature to randomly split audiences at the same time, then compares a chosen metric to learn which version performs better with statistical confidence."
taxonomies:
  glossary_category: [analytics]
related_terms: [conversion-rate-optimization, landing-page, call-to-action-cta, click-through-rate]
related_service: lead-generation
author: joanna-okedara-kalu
reviewer: chinedu-emmanuel-kalu
internal_links:
  - /services/lead-generation/
  - /glossary/conversion-rate-optimization/
  - /glossary/landing-page/
  - /tools/email-subject-line-analyzer/
rank_math_description: "A/B testing explained: how a valid test works, a sample-size worked example, the mistakes that produce false winners, and how to test without hurting SEO."
---

A/B testing is a simple experiment: show version A to half your audience and version B to the other half, at the same time, and measure which one gets more of the result you care about. Because the only difference is the change you made, a clear winner tells you that change caused the difference. It's the most reliable way to settle debates about headlines, forms, offers and subject lines.

:::takeaways
- Test one meaningful change against one primary metric, decided before you start.
- Work out the sample size before you launch, and don't stop the test the moment one version looks ahead.
- Low-traffic pages can't support reliable tests of small changes. Test bigger changes or use other research.
- Testing website variations doesn't hurt SEO if you avoid cloaking, use 302 redirects and end tests promptly.
:::

## How an A/B test works

1. **Hypothesis:** "Because visitors abandon our long form, cutting it to four fields will increase demo requests."
2. **Variants:** A (control) is the current page. B is the same page with the shorter form.
3. **Random split:** a testing tool assigns each visitor to A or B and keeps them there.
4. **Primary metric:** demo requests per visitor. Secondary metrics, such as lead quality, are checked but don't decide the winner.
5. **Decision:** once the planned sample is reached, compare results and check that the difference is statistically significant.

## How much traffic do you need?

This is where most tests go wrong. A common rule of thumb for 80% power at 5% significance is: visitors per variant ≈ 16 × p × (1 − p) ÷ d², where p is your baseline conversion rate and d is the absolute lift you want to detect.

### A worked example

Your landing page converts at 3% and you want to detect a lift to 4% (d = 0.01). Use the midpoint, p = 0.035:

16 × 0.035 × 0.965 ÷ 0.0001 ≈ **5,400 visitors per variant**, or about 10,800 in total.

If the page gets 1,500 visitors a month, that test would take over seven months. Better options: test a bolder change (a bigger expected lift needs far fewer visitors), test higher up the funnel where volumes are larger, such as ad or email clicks, or use research-led [conversion rate optimization](/glossary/conversion-rate-optimization/) instead.

:::myth-fact
Myth: Stop the test as soon as the tool shows 95% significance.
Fact: Checking repeatedly and stopping at the first significant result inflates false positives. Decide the sample size in advance and run for full weeks to cover weekday and weekend behavior.
Myth: A/B tests can hurt your search rankings.
Fact: Google's testing guidance says small variations generally don't affect search performance, as long as you don't cloak, use 302 redirects for redirect tests, add rel="canonical" to variant URLs and end tests promptly.
:::

## Example from our work

:::quote "Joanna Okedara-Kalu" "Founder & CEO, TechDella"
The mistake I see most often is a founder running a button-color test on a page that gets a few hundred visits a month, then trusting whatever the tool says after a week. If you can't reach a proper sample in a reasonable time, don't test. Talk to five customers, fix what they stumble on, and save experiments for the places where you actually have volume.
:::

## How to run A/B testing, step by step

:::steps "From idea to decision"
1. Start from evidence | Base the hypothesis on data: analytics drop-off, recordings, customer feedback.
2. Pick one primary metric | Choose the metric closest to revenue that has enough volume, such as form submissions rather than closed deals.
3. Calculate sample size | Use the formula above or a calculator, and estimate how long the test will take at current traffic.
4. Build and QA both versions | Check both on mobile and desktop, and confirm tracking fires for each variant.
5. Run for full weeks | Don't peek and stop early. Avoid launching during holidays or big campaigns that skew traffic.
6. Decide and document | Ship the winner, or keep the control if there's no clear difference. Record what you learned.
:::

### What's worth testing

| Area | High-impact tests | Low-impact tests |
|---|---|---|
| [Landing pages](/glossary/landing-page/) | Offer, headline, form length, proof | Button color, icon style |
| Ads | Value proposition, audience angle | Minor word swaps |
| Email | Subject line, offer, send time | Preheader punctuation |
| [Calls to action](/glossary/call-to-action-cta/) | What the CTA offers | Button shape |

For email, check subject lines before you test them with our free [email subject line analyzer](/tools/email-subject-line-analyzer/).

:::cta "Want a testing program that produces real answers?" "See our lead generation service" /services/lead-generation/
We plan tests your traffic can support and connect results to qualified leads, not just clicks.
:::

### Tools in 2026

Google Optimize, once the default free tool, was [shut down on September 30, 2023](https://support.google.com/optimize/answer/12979939). Common options now include dedicated testing platforms, built-in testing in landing page builders, and native experiments in Google Ads, Meta Ads and most email platforms.

## Related terms

- [Conversion rate optimization](/glossary/conversion-rate-optimization/): the wider program A/B tests belong to.
- [Landing page](/glossary/landing-page/): the most common place to test.
- [Call to action (CTA)](/glossary/call-to-action-cta/): a frequent test subject.
- [Click-through rate](/glossary/click-through-rate/): a common primary metric for ad and email tests.

:::faq
### How long should an A/B test run?
Until it reaches the sample size you calculated in advance, and for at least one or two full weeks to cover weekly patterns. If the required sample would take months, the test isn't practical. Test a bigger change or a higher-traffic step instead.
### What is the difference between A/B testing and multivariate testing?
An A/B test compares two or more complete versions. A multivariate test changes several elements at once and measures each combination. Multivariate tests need much more traffic, so most small and mid-sized B2B sites should stick to A/B tests.
### Can I A/B test with low traffic?
Only for large changes, or on higher-volume steps such as ad clicks or email opens. For low-traffic pages, use qualitative research, user testing and before-and-after comparisons, and accept less certainty.
### What if the test shows no difference?
That's a result too. It tells you the change didn't matter much to visitors, so you can keep the simpler version and move to a bolder idea. Document it so nobody re-runs the same test later.
:::

## How TechDella can help

Our [lead generation service](/services/lead-generation/) runs structured tests on landing pages, ads and emails, sized to your real traffic.
