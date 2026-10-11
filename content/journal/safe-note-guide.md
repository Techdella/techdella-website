---
title: "SAFE Note Guide: Clauses, Caps and Dilution Explained"
slug: safe-note-guide
excerpt: "How Y Combinator's post-money SAFE works: the valuation cap, discount, MFN and pro rata side letter, what happens in an acquisition or shutdown, and the dilution math founders should run before signing."
primary_kw: safe note
secondary_kws: [post-money safe, safe valuation cap, safe discount rate, safe vs convertible note, pro rata side letter]
intent: informational
audience: "Pre-seed and seed founders raising on SAFEs, including founders in Africa"
angle: "Founders sign SAFEs one at a time and feel the dilution all at once. We explain each clause from YC's own documents and show the cap-table arithmetic step by step."
proof: "Y Combinator documents page and Post-Money SAFE User Guide."
author: omolola-akiyode
reviewer: joanna-okedara-kalu
tldr: "A SAFE (simple agreement for future equity) is an investment that converts into shares at a later priced round, created by Y Combinator in 2013 and standardized as the post-money SAFE in 2018. It is not debt: no interest and no maturity date. The valuation cap sets the investor's ownership up front (investment ÷ post-money cap), so you can calculate your dilution before you sign."
categories: [startups]
cta_service: lead-generation
rank_math_description: "SAFE note guide: how YC's post-money SAFE works, valuation caps, discounts, MFN and pro rata rights, plus worked dilution math to run before you sign."
internal_links:
  - /startup-funding-stages-for-african-founders/
  - /finding-startup-grants-in-africa/
  - marketing:/glossary/safe-agreement/
  - marketing:/glossary/pre-seed-funding/
  - marketing:/services/lead-generation/
  - studios:/services/mvp-development/
---

A SAFE note (simple agreement for future equity) is an early-stage investment where an investor gives you money now in exchange for shares later, when you raise a priced equity round. Y Combinator introduced the SAFE in 2013 and standardized on the **post-money SAFE** in 2018. A SAFE is not debt: it has no interest rate and no maturity date. Its most important term, the post-money valuation cap, tells you exactly how much of your company each investor will own when it converts, so you can do the dilution math before you sign.

This guide explains each SAFE clause using YC's own documents, shows the arithmetic with worked examples, and lists the mistakes that quietly reshape cap tables.

:::callout warning "This is not legal advice"
This guide explains how YC's standard SAFE documents work so you can have better conversations with your lawyer and investors. It is not legal, tax or investment advice. SAFE terms interact with company and securities law where your company is incorporated; have a qualified lawyer review any SAFE before you sign it.
:::

:::takeaways
- Use YC's **official, unedited forms** from [ycombinator.com/documents](https://www.ycombinator.com/documents). Edited "SAFE-like" documents are where surprises hide.
- **Ownership = investment ÷ post-money valuation cap**, measured after all SAFEs but before the priced round.
- SAFE holders are diluted by the next priced round, just like founders.
- Pro rata rights are **not** in the standard SAFE; they're in an optional side letter.
:::

## What is a SAFE note?

YC describes the SAFE as a replacement for convertible notes: a single, short document where founders and investors usually negotiate just one item, the valuation cap. Because a SAFE has no maturity date, there is no deadline to extend and no interest to renegotiate. Each investor can sign and wire as soon as they're ready, so you don't need everyone to close on the same day ([YC Post-Money SAFE User Guide](https://www.ycombinator.com/documents)).

:::stats "The SAFE in three facts"
2013 | Y Combinator introduced the original SAFE as an alternative to convertible notes | Y Combinator, Post-Money SAFE User Guide | https://www.ycombinator.com/documents
2018 | YC standardized on the post-money SAFE, which makes ownership calculable at signing | Y Combinator, documents page | https://www.ycombinator.com/documents
3 | standard US post-money SAFE forms: valuation cap only, discount only, and uncapped MFN | Y Combinator, documents page | https://www.ycombinator.com/documents
:::

### Post-money vs pre-money SAFE

The original SAFE used a pre-money cap, which made dilution hard to predict: your ownership depended on how much you raised on other SAFEs and on an option-pool increase negotiated years later. The post-money SAFE fixes this. The cap is measured **after** all SAFE money, so each investor's ownership is simply their investment divided by the cap. YC's documents page no longer offers a pre-money SAFE form.

## The SAFE note clauses that matter

### 1. Post-money valuation cap

The cap sets the most the investor will "pay" per share when the SAFE converts. If your priced round values the company above the cap, SAFE investors convert at the cap and get more shares than new investors would for the same money.

**Worked example.** You raise $500,000 on a $5 million post-money cap. The investor will own $500,000 ÷ $5,000,000 = **10%** of the company immediately after the SAFEs convert, before the new round's money. If your Series A later values you at $15 million, they still convert at the $5 million cap.

### 2. Discount rate

A discount lets SAFE investors buy shares in the next round at a lower price than new investors. YC's wording catches people out: the "Discount Rate" is the percentage of the price the investor pays, so a 20% discount is written as a **Discount Rate of 80%**. If Series A investors pay $1.00 per share, the SAFE investor pays $0.80.

YC's current standard forms offer a cap *or* a discount, not both. YC's user guide describes a cap-and-discount version, where the investor gets whichever gives them the better price. If an investor asks for both, model both outcomes.

### 3. Conversion in an equity financing

When you sell preferred stock in a priced round, outstanding SAFEs convert automatically, with no minimum round size. The investor doesn't get to choose, and the SAFE then ends.

### 4. Liquidity event: acquisition or IPO

If the company is acquired or goes public before the SAFE converts, the investor receives the **greater of** their money back (the purchase amount) or what they'd get if the SAFE had converted to common stock at the cap. In this situation the SAFE ranks behind creditors and debt (including convertible notes), alongside other SAFEs and preferred stock, and ahead of common stockholders, which usually includes founders.

### 5. Dissolution: if the company shuts down

If the company winds down, SAFE holders are entitled to their purchase amount back, with the same priority as above: after creditors, alongside preferred stock, before common stock. In practice, if there isn't enough money left after creditors, they may receive less or nothing.

### 6. MFN (most favored nation)

YC's uncapped MFN SAFE has no cap and no discount. If you later issue SAFEs on better terms, the MFN investor can choose to amend their SAFE to match. The MFN doesn't extend to side letters. Several MFN SAFEs outstanding can make later negotiations awkward, because each new term ripples back.

### 7. Pro rata side letter

Pro rata rights let an investor buy into your next priced round to keep their ownership. In YC's documents they live in an **optional, standardized side letter**, not the SAFE itself. Before granting them, estimate how much of the next round they could take; YC's guide includes the formula. Giving pro rata to many small investors can crowd out the lead investor in your Series A.

:::myth-fact
Myth: A SAFE is a kind of loan, so you'll owe the money back if things go badly.
Fact: The post-money SAFE has no interest and no maturity date. Investors get money back only in a liquidity or dissolution event, and in a shutdown they rank behind creditors.
Myth: SAFE investors aren't diluted when you raise your Series A.
Fact: YC's guide says SAFE ownership is "post-safes," not post-Series A. SAFE holders are diluted by the priced round just like founders.
Myth: A 20% discount means a Discount Rate of 20%.
Fact: In YC's documents, a 20% discount is a Discount Rate of 80%: the investor pays 80% of the new round's price.
:::

## Run the dilution math before you sign

The post-money SAFE makes this simple addition and division. Here's a worked example for one founding team.

:::steps "Worked example: two SAFEs, then a Series A"
1. Start | Founders own 100% of the company.
2. First SAFE | $500,000 at a $5.5 million post-money cap: 500,000 ÷ 5,500,000 ≈ 9.1%.
3. Second SAFE | $500,000 at an $8.3 million post-money cap: 500,000 ÷ 8,300,000 ≈ 6.0%.
4. After SAFEs convert | SAFE investors own about 15.1%; founders own about 84.9%.
5. Series A sells 20% | Everyone is diluted by 20%: founders 84.9% × 0.8 ≈ 67.9%; SAFE investors 15.1% × 0.8 ≈ 12.1%; new investors 20%.
6. Add an option pool | If the Series A also requires a new 10% option pool, everyone shrinks again. Model it now, not at the term sheet.
:::

This example matches the approach in YC's quick-start guide, which shows how raising $500,000 on each of those two caps sells about 15% of the company. The numbers ignore existing option grants for simplicity; your lawyer or a cap-table tool should run the real version.

If the priced round comes in **below or close to** your cap, SAFE investors can convert into more than these estimates suggest. YC's guide flags this; it's another reason not to set caps far above what your traction supports.

:::cta "The best SAFE terms come from real traction" "Build your pipeline" marketing:/services/lead-generation/
Investors negotiate on evidence. We build the offers, landing pages and follow-up that turn early interest into customers you can point to.
:::

## SAFE vs convertible note vs priced round

| | Post-money SAFE | Convertible note | Priced round |
|---|---|---|---|
| Legal form | Right to future equity | Debt that converts | Shares issued now |
| Interest and maturity | None | Usually both | Not applicable |
| What you negotiate | Mainly the cap (or discount) | Cap, discount, interest, maturity | Valuation, board, rights, many terms |
| Closing | Investor by investor | Investor by investor | Usually one coordinated close |
| Legal cost and time | Low | Low to medium | Highest |
| Ownership clarity | Clear at signing (post-money) | Depends on terms | Clear immediately |

## SAFEs for founders outside the US

YC's standard SAFE forms are written for US companies. YC also publishes forms for companies formed in Canada, the Cayman Islands and Singapore, and tells founders to consult a lawyer licensed in the country where the company was formed. If your company is incorporated in Nigeria, Kenya, South Africa or elsewhere, a US-form SAFE may not work as intended under local company law. Get local advice before using one, and make sure the instrument fits how you plan to raise later. For the bigger picture of how SAFEs fit into African fundraising, see our guide to [startup funding stages](/startup-funding-stages-for-african-founders/).

:::quote "Joanna Okedara-Kalu" "Founder & CEO, TechDella"
I'm not a lawyer, and founders shouldn't take SAFE terms from their marketing team. What I see from the growth side is this: founders with paying customers and a clear pipeline negotiate from a stronger position than founders with a great deck. If you're about to raise, spend as much energy on traction you can show as on the instrument you sign.
:::

## Common SAFE mistakes founders make

- **Not tracking SAFEs on the cap table.** YC strongly recommends keeping an accurate cap table. Add every SAFE the day you sign it.
- **Stacking SAFEs without a total in mind.** Decide how much of the company you're willing to sell in this "round" and stop when you reach it.
- **Setting a cap your traction can't support.** If the priced round comes in near or below the cap, dilution is worse than you planned.
- **Editing the standard form.** Custom changes cost legal fees and may not convert the way everyone expects.
- **Granting pro rata to everyone.** It can limit how much of your Series A the lead investor can take.
- **Forgetting the option pool.** Future pool increases dilute founders and SAFE holders alike.

:::checklist "Before you sign a SAFE"
- You're using YC's current form, downloaded from YC, without edits
- You know whether it's a cap, discount or MFN SAFE
- You've calculated this investor's ownership (investment ÷ post-money cap)
- You've added it to a cap table with all earlier SAFEs and option promises
- You've decided whether to grant a pro rata side letter, and to whom
- A lawyer in your company's jurisdiction has reviewed it
:::

:::faq
### What is a SAFE note in simple terms?
A SAFE is an agreement where an investor gives your startup money now in exchange for shares later, usually when you raise a priced round. Y Combinator created it in 2013 and standardized the post-money version in 2018. It has no interest or maturity date.
### Is a SAFE note debt?
No. Unlike a convertible note, a post-money SAFE has no interest rate and no maturity date. If the company shuts down, SAFE holders are entitled to their money back only after creditors are paid, alongside preferred stockholders.
### How do I calculate dilution from a SAFE?
With a post-money SAFE, divide the investment by the post-money valuation cap. $500,000 on a $5 million cap is 10% after the SAFEs convert. Add all your SAFEs together, then apply the dilution from your priced round and any option pool increase.
### What is a good valuation cap for a SAFE?
There's no universal number. The cap reflects your traction, market and investor demand. Work backward from the ownership you're willing to sell: if you want to raise $1 million and sell about 15%, YC's guide shows a post-money cap of roughly $6.7 million.
### Do SAFE investors get pro rata rights?
Not automatically. In YC's documents, pro rata rights sit in an optional side letter. You decide whether to grant one, and to which investors.
### Can founders outside the US use a YC SAFE?
YC's main forms are for US companies, with separate forms for Canada, the Cayman Islands and Singapore. Founders with companies incorporated elsewhere should take advice from a lawyer licensed in that country before using a SAFE.
:::

## Final thought

A SAFE is simple to sign and easy to underestimate. Run the math for every SAFE, keep the cap table current and stop when you've sold what you planned. The strongest negotiating position is traction, so spend the money on the product and customers that will justify a better cap next time. If you're still building that product, [Techdella Studios](studios:/services/mvp-development/) can help you scope it.
