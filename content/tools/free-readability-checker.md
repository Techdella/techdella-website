---
title: "Free Readability Checker"
slug: free-readability-checker
excerpt: "A free readability checker that scores your copy with Flesch Reading Ease, Flesch-Kincaid grade and the Gunning Fog index, then lists the sentences to fix first."
rank_math_description: "Free readability checker: get your Flesch Reading Ease score, grade level, Fog index and long-sentence list. Your text stays in your browser while it runs."
tool_component: readability-checker
primary_kw: readability checker
secondary_kws: [readability score, flesch reading ease, flesch kincaid grade level, reading level checker, gunning fog index]
intent: transactional
audience: "Founders, marketers and writers editing web pages, emails and guides for busy readers"
author: joanna-okedara-kalu
reviewer: chinedu-emmanuel-kalu
gate: email
ad_slots: true
how_to:
  - "Paste your text | A page, an email, a post or a section of a guide. You need at least 20 words, and the box takes up to 60,000 characters. Plain text works best, so paste without navigation or footers."
  - "Press Check readability | The tool splits your text into sentences and words, counts syllables, and calculates every score in your browser."
  - "Enter your email once to see results | The first time you run the tool it asks for your email. After that, this browser shows results straight away."
  - "Read the headline score | Flesch Reading Ease from 0 to 100, with a label from Very difficult to Easy and one line of advice."
  - "Check the numbers and What to fix | Grade level, Fog index, word and sentence counts and reading time, then three checks: average sentence length, sentences over 25 words, and possible passive sentences."
  - "Fix the longest sentences and run it again | The tool lists up to five sentences over 25 words, longest first. Split them, swap long words for short ones, and check again."
faq:
  - "What is a good Flesch Reading Ease score? | Higher is easier. Our checker labels 60 to 79 as Plain English, clear for most adult readers and a sensible target for web pages and email. 80 and above is Easy. Below 50 is Difficult, and below 30 is Very difficult. Technical audiences can handle lower scores, but shorter sentences help every reader."
  - "How is the Flesch Reading Ease score calculated? | Rudolf Flesch published the formula in 1948. Reading Ease equals 206.835, minus 1.015 times the average words per sentence, minus 84.6 times the average syllables per word. Long sentences and long words both pull the score down. Our checker caps the result between 0 and 100."
  - "What is the Flesch-Kincaid grade level? | It turns the same two inputs into a US school grade. Grade equals 0.39 times words per sentence, plus 11.8 times syllables per word, minus 15.59. It comes from a 1975 US Navy study by Kincaid and colleagues. A score of 8 means a typical eighth grader could follow the text."
  - "What does the Gunning Fog index measure? | It estimates the years of schooling a reader needs to understand the text on a first read. Our checker calculates it as 0.4 times the sum of average sentence length and the percentage of words with three or more syllables. It weights long words more heavily than Flesch does."
  - "Why might my score differ from other readability tools? | Tools count syllables and sentences differently. Ours uses a common heuristic based on vowel groups and silent endings, so a word can be off by a syllable. It also treats line breaks before bullet points as sentence ends. Compare scores within one tool, not across tools."
  - "Does a better readability score help SEO? | Not directly. Google doesn't document any readability formula as a ranking factor. Clear writing helps people find and understand the answer faster, which is what Google's helpful content guidance asks for. Use the score to find hard sentences, then judge the fix by whether a reader would understand it."
  - "Is my text stored or sent anywhere? | No. The scores are calculated in your browser and your text isn't uploaded. When you enter your email to see results, only a one-line summary of the scores is sent with it, such as your reading ease, grade and word count."
---

A readability checker measures how hard your writing is to read. Paste your text and this tool returns a Flesch Reading Ease score from 0 to 100, a Flesch-Kincaid grade level, a Gunning Fog index and your reading time, then lists the long and passive sentences worth fixing first. It's free, your text stays in your browser, and it asks for your email once to show results.

It's built for founders, marketers and writers editing web pages, emails and guides for readers who skim.

:::takeaways
- Flesch Reading Ease runs from 0 to 100. Higher means easier. Aim for 60 or above on most web pages and emails.
- Two things drive every score: how long your sentences are and how long your words are.
- Fix the longest sentences first. The tool lists them for you, longest first.
- Formulas count; they don't understand. Use the score to find problems, then judge the fix yourself.
:::

## What this readability checker measures

The tool calculates three classic formulas plus some plain counts. All three formulas use sentence length; they differ in how they treat long words.

| Score | Formula used in this tool | What it tells you |
|---|---|---|
| **Flesch Reading Ease** | 206.835 − 1.015 × (words ÷ sentences) − 84.6 × (syllables ÷ words), kept between 0 and 100 | Overall ease. Higher is easier |
| **Flesch-Kincaid grade** | 0.39 × (words ÷ sentences) + 11.8 × (syllables ÷ words) − 15.59 | The US school grade that matches the text |
| **Gunning Fog index** | 0.4 × (words ÷ sentences + 100 × words of 3+ syllables ÷ words) | Years of schooling needed on a first read |

Rudolf Flesch published Reading Ease in ["A new readability yardstick"](https://doi.org/10.1037/h0057532) in the *Journal of Applied Psychology* in 1948. The grade-level version comes from a [1975 US Navy report by Kincaid, Fishburne, Rogers and Chissom](https://stars.library.ucf.edu/istlibrary/56), which recalculated three formulas for Navy training manuals. The Fog index comes from Robert Gunning's 1952 book *The Technique of Clear Writing*.

:::stats "Where the numbers come from"
531 | Navy enlisted personnel tested to derive the Flesch-Kincaid grade formula, using 18 passages from training manuals | Kincaid et al., US Navy, 1975 | https://stars.library.ucf.edu/istlibrary/56
238 | words per minute: average silent reading rate for English non-fiction, across 190 studies | Brysbaert, Journal of Memory and Language, 2019 | https://doi.org/10.1016/j.jml.2019.104047
:::

## How it works

When you press **Check readability**, the tool does the following, all in your browser:

1. **Counts words.** A word is any run of letters or numbers, including apostrophes and hyphens inside it. So `end-to-end` counts as one word.
2. **Splits sentences.** It ends a sentence at `.`, `!` or `?` followed by a space, at a blank line, and at a line break before a bullet (`-`, `*` or `•`). It first removes the dots from common abbreviations (`e.g.`, `i.e.`, `etc.`, `vs.`, `Mr.`, `Mrs.`, `Ms.`, `Dr.`, `Inc.`, `Ltd.`) so they don't end a sentence early.
3. **Counts syllables.** It uses a common rule of thumb: words of three letters or fewer count as one syllable; otherwise it strips silent endings like `-es`, `-ed` and a final `-e`, then counts groups of vowels. It's fast and close, but not a dictionary, so a word can be off by one.
4. **Calculates the three scores** with the formulas above.
5. **Runs three checks.** Average sentence length (20 words or fewer passes), sentences over 25 words (none passes), and possible passive sentences. A sentence is flagged as possibly passive when a form of "to be" (is, are, was, were, be, been, being, am) is followed by a word ending in `-ed` or `-en`, with an optional `-ly` word between. The check warns when more than one sentence, and more than 10% of all sentences, are flagged.
6. **Estimates reading time** at 230 words per minute, rounded, with a one-minute minimum. That's close to the 238 words per minute Brysbaert's 2019 review found for non-fiction.

The tool needs at least 20 words, because scores on one or two sentences swing wildly.

## How to read your results

The headline is Flesch Reading Ease. The tool gives each range a label and a line of advice:

| Reading Ease | Label | What it means | What to do |
|---|---|---|---|
| 80–100 | Easy | Plain enough for almost anyone to read quickly | Keep it. Check it isn't choppy |
| 60–79 | Plain English | Clear for most adult readers | A good target for web pages and email |
| 50–59 | Fairly difficult | Readable, but some effort | Shorten sentences, swap a few long words |
| 30–49 | Difficult | Hard work for many readers | Split long sentences, replace long words |
| 0–29 | Very difficult | Reads like an academic paper | Rewrite the longest sentences first |

The **What to fix** checks are where the editing starts. "Improve" next to average sentence length means your sentences average more than 20 words. "Improve" next to long sentences means at least one runs past 25 words; the list below shows up to five, longest first. Passive flags are hints, not errors. "The report was approved by the board" is passive; "The board approved the report" is clearer. But a phrase like "is often written" can trip the check, so read each one.

## A worked example

Here's a 38-word paragraph in two sentences, the kind that ends up on a lot of service pages:

> Our comprehensive, end-to-end solution enables organizations to optimize their marketing operations through the strategic utilization of automation technologies. Implementation is typically completed by our experienced team within a timeframe that is determined by the complexity of existing infrastructure.

The tool counts **38 words**, **2 sentences** and **92 syllables**.

- Words per sentence: 38 ÷ 2 = **19**
- Syllables per word: 92 ÷ 38 = **2.421**
- Reading Ease: 206.835 − (1.015 × 19) − (84.6 × 2.421) = 206.835 − 19.29 − 204.82 = **−17.3**, shown as **0** because the tool keeps scores between 0 and 100
- Grade: (0.39 × 19) + (11.8 × 2.421) − 15.59 = 7.41 + 28.57 − 15.59 = **20.4**
- Fog: 19 of the 38 words have three or more syllables, so 0.4 × (19 + 50) = **27.6**

Notice what didn't trigger. Neither sentence is over 25 words, and the average of 19 passes the length check. The problem is the words: "organizations", "utilization" and "implementation" are five syllables each. The second sentence is also flagged as passive ("is typically completed").

Now the rewrite, 32 words in four sentences:

> We set up marketing automation for you. Your team spends less time on manual work. Most setups take a few weeks. How long yours takes depends on the tools you already use.

- Words per sentence: 32 ÷ 4 = **8**
- Syllables per word: 42 ÷ 32 = **1.3125**
- Reading Ease: 206.835 − (1.015 × 8) − (84.6 × 1.3125) = 206.835 − 8.12 − 111.04 = **87.7** (Easy)
- Grade: (0.39 × 8) + (11.8 × 1.3125) − 15.59 = 3.12 + 15.49 − 15.59 = **3.0**
- Fog: 3 complex words out of 32, so 0.4 × (8 + 9.4) = **7.0**

Same message, shorter words, active voice. The reader knows who does the work and how long it takes.

:::callout note "From our work"
[CONFIRM: Joanna — add one first-hand example here: what we saw with a client (with permission), a screenshot, or our own number.]
:::

## Best practices for readable web copy

:::checklist "Edit in this order"
- Fix every sentence over 25 words first. Split it at "and", "which" or "because".
- Keep the average sentence at 20 words or fewer. Mix short and medium sentences so it doesn't sound robotic.
- Swap long words for short ones: use for utilize, help for facilitate, start for commence, to for in order to.
- Put the actor first. "We fixed the bug", not "The bug was fixed".
- Answer the reader's question in the first two or three sentences.
- Use lists and tables for steps and comparisons instead of long paragraphs.
- Check again after editing. Your score should move up; if it doesn't, look at word length.
:::

Readable copy also makes a page easier to quote. When a page answers a question in two clear sentences, it's simpler for a search engine or AI assistant to pull that answer out. Our guide on [how to get cited in AI search](journal:/how-to-get-cited-in-ai-search/) covers the rest of that work, and our glossary entry on [answer engine optimization](/glossary/aeo/) explains the term. Good [copywriting](/glossary/copywriting/) does the same job for people.

:::cta "Want pages that rank and read well?" "See our SEO service" /services/seo/
We write and rewrite service pages and guides that answer the search, in plain language, with proof.
:::

## Common readability mistakes

:::myth-fact
Myth: A higher readability score always means better writing.
Fact: The formulas count sentence and word length, not meaning. A page of very short sentences can score high and still say nothing useful.
Myth: Google ranks pages by readability score.
Fact: Google doesn't document any readability formula as a ranking factor. Clear writing helps because people can understand and use the page, which is what its helpful content guidance asks for.
Myth: Technical topics can't be written plainly.
Fact: The terms may need to stay, but the sentences around them can be short. Define a term once, then use it consistently.
:::

Other mistakes worth avoiding:

- **Chasing the number.** Cutting every sentence to five words makes copy choppy and hard to follow. Aim for the Plain English band, not 100.
- **Scoring the whole page with the menu in it.** Navigation and footer text add short fragments that inflate your score. Paste only the body copy.
- **Comparing scores across tools.** Each tool counts syllables and sentences slightly differently. Track one tool over time.

## Where to go next

If you're rewriting service pages, start with the ones that matter most for leads. Our [SEO team](/services/seo/) rewrites pages so they answer the search clearly, and our [AI search optimization](/services/ai-search-optimization/) work structures them so AI assistants can quote them. A [growth audit](/growth-audit/) will tell you which pages to fix first.
