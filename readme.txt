=== Ligase ===
Contributors: marcinzmuda
Tags: schema, json-ld, seo, structured data, rich results, ai search, schema.org
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 8.0
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Schema.org JSON-LD markup for WordPress blogs. Article, Person, Organization, BreadcrumbList, FAQPage, HowTo, VideoObject — with AI Search Readiness scoring.

== Description ==

Ligase automatically generates complete schema.org JSON-LD markup for your WordPress blog, optimized for both Google Rich Results and AI search engines (Google AI Overviews, ChatGPT, Perplexity).

**Key Features:**

* **Complete @graph schema** — Article/BlogPosting, Person, Organization, WebSite, BreadcrumbList in a single linked JSON-LD block
* **AI Search Readiness Score** — 0-100 score showing how ready your site is for AI citation
* **E-E-A-T Author Scoring** — Per-author expertise scores with actionable recommendations
* **Schema Auditor** — Scan, supplement, or replace weak schema from other plugins (Yoast, Rank Math, AIOSEO)
* **Entity Detection** — 4-level pipeline: WordPress native → content structure → NER → Wikidata lookup
* **sameAs + Wikidata** — Link your blog and authors to Wikidata for entity disambiguation
* **knowsAbout** — Declare expertise topics for Organization and Person entities
* **Auto YouTube detection** — Generates VideoObject schema from embedded YouTube videos
* **FAQ & HowTo blocks** — Gutenberg blocks with automatic schema generation
* **Google Schema Changelog** — Stay informed about Google's rich result changes
* **Import/Export** — Migrate settings between sites or from other SEO plugins

**Supported Schema Types:**

* Article / BlogPosting / NewsArticle
* Person (with E-E-A-T signals)
* Organization (with logo, sameAs, knowsAbout)
* WebSite (with SearchAction)
* BreadcrumbList
* FAQPage
* HowTo
* VideoObject
* Review
* QAPage
* ClaimReview
* DefinedTermSet
* SoftwareApplication
* AudioObject
* Course
* Event

== Installation ==

1. Upload the `ligase` folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go to **Ligase → Ustawienia** to configure your organization details
4. Add author profiles with LinkedIn, Wikidata links for E-E-A-T signals

== Frequently Asked Questions ==

= Does Ligase work alongside Yoast SEO? =
Yes. In default mode, Ligase detects Yoast and skips output to avoid duplicates. Enable Standalone Mode to suppress Yoast schema and use Ligase exclusively.

= Does Ligase work with Rank Math and All in One SEO? =
Yes. The same conflict detection applies to Rank Math, AIOSEO, SEOPress, The SEO Framework, and Slim SEO. In default mode Ligase defers to them; in Standalone Mode it replaces their output.

= Can I import settings from Yoast or Rank Math? =
Yes. Go to Ligase > Narzedzia > Import z wtyczek SEO. One click imports organization name, logo, and social links.

= What is the AI Search Readiness Score? =
A 0-100 score measuring how well your site's schema supports citation by AI search engines like Google AI Overviews, ChatGPT, and Perplexity. Higher scores mean better entity linking, sameAs verification, and content structure.

= Does FAQPage schema still work? =
Google restricted FAQPage rich results to gov/health sites in 2024, but the schema still provides semantic value for AI Overviews and Bing Copilot.

= Will schema be lost if I deactivate the plugin? =
Your settings and post meta are preserved on deactivation. Only uninstalling removes all data.

= Does Ligase support WPML or Polylang? =
Yes. Ligase auto-detects WPML and Polylang, sets correct inLanguage per translation, and adds sameAs links between translated versions.

= How does the Google Search Console integration work? =
Ligase uses a Service Account (no OAuth redirect). Create a Service Account in Google Cloud Console, add it to GSC, and paste the JSON key into the Ligase dashboard.

== Screenshots ==

1. Dashboard with AI Search Readiness Score and schema coverage stats
2. Schema Auditor scanning existing markup from other plugins
3. Post editor with Schema Markup metabox and type toggles
4. Posts list with per-post schema score and quick actions
5. Entity management with E-E-A-T author scores
6. Tools page with SEO plugin importer and schema validator
7. Gutenberg sidebar panel with live schema validation
8. Google Search Console rich results dashboard

== Changelog ==

= 2.0.0 =
* Google Search Console integration (Service Account JWT, AES-256-CBC encryption)
* Gutenberg sidebar: live schema validation with errors/warnings
* Import from Yoast SEO, Rank Math, All in One SEO
* Weekly schema health report (WP-Cron email)
* WPML / Polylang multilingual support
* Schema validator tool
* 7 new schema types: QAPage, ClaimReview, DefinedTermSet, SoftwareApplication, AudioObject, Course, Event
* BlogPosting: Speakable, accessMode, potentialAction, about, mentions, isBasedOn, hasPart, temporalCoverage
* Organization: telephone, contactPoint, description, founder, employee
* Person: honorificPrefix, alumniOf, hasCredential, mainEntityOfPage
* Review: name, reviewBody, publisher
* VideoObject: @id, inLanguage, duration
* Metabox with 9 toggleable schema types and deprecated-type tooltips
* Dashboard conflict banner for active SEO plugins
* Bulk select and fix in posts list
* FAQ block live word counter (40-60 words optimal)

= 1.0.0 =
* Initial release
* Article/BlogPosting, Person, Organization, WebSite, BreadcrumbList schema
* FAQPage, HowTo, VideoObject, Review schema
* AI Search Readiness Score
* E-E-A-T author scoring
* Schema Auditor with 3 modes (scan/supplement/replace)
* Entity detection pipeline with NER and Wikidata
* Gutenberg FAQ and HowTo blocks
* Import/Export settings

== Upgrade Notice ==

= 2.0.0 =
Major update: GSC integration, 7 new schema types, Gutenberg sidebar validator, SEO plugin importer, multilingual support.

= 1.0.0 =
First release of Ligase.

= 1.0.0 =
* Initial release
* Article/BlogPosting, Person, Organization, WebSite, BreadcrumbList schema
* FAQPage, HowTo, VideoObject, Review schema
* AI Search Readiness Score
* E-E-A-T author scoring
* Schema Auditor with 3 modes (scan/supplement/replace)
* Entity detection pipeline with NER and Wikidata
* Gutenberg FAQ and HowTo blocks
* Import/Export settings
* Google Schema Changelog widget

== Upgrade Notice ==

= 1.0.0 =
First release of Ligase.
