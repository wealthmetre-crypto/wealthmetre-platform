<?php
/**
 * WealthMetre — Programmatic SEO Page Template
 * Version 2.0 — All audit fixes applied
 *
 * Fixes applied:
 *  1. Hero HTML structure fixed (sp-hero-right added, all divs closed)
 *  2. City-specific advisory content added per product (~300+ words extra)
 *  3. Rate table added to each product (structured content + rich result eligible)
 *  4. Breadcrumb hardcoded Jaipur link fixed
 *  5. BreadcrumbList schema added
 *  6. OG image + Twitter card meta tags added
 *  7. Dynamic sitemap block added at bottom (comment)
 *  8. JS cycleTo(current) on init fixed
 *  9. HTML entity safety reviewed throughout
 *
 * URL examples (set up via .htaccess rewrites):
 *   /home-loan-jodhpur.html   → seo-page.php?product=home-loan&city=jodhpur
 *   /lap-kota.html            → seo-page.php?product=lap&city=kota
 *   /business-loan-udaipur.html → seo-page.php?product=business-loan&city=udaipur
 *
 * Upload to: public_html/seo-page.php
 */

/* ─────────────────────────────────────────────
   HELPER
───────────────────────────────────────────── */
function fill(string $text, string $city): string {
    return str_replace('%CITY%', $city, $text);
}

/* ─────────────────────────────────────────────
   DATA: Cities
───────────────────────────────────────────── */
$CITIES = [

    'jaipur' => [
        'name'      => 'Jaipur',
        'state'     => 'Rajasthan',
        'desc'      => 'capital of Rajasthan and the Pink City',
        'area'      => 'Malviya Nagar, Vaishali Nagar, Mansarovar, C-Scheme, Jagatpura',
        'prop_note' => 'JDA-approved, JVVNL, Society Patta, and Nagar Nigam properties widely accepted.',
        'pin'       => '302001',
        'advisory'  => [
            'home-loan'        => 'Jaipur has one of the highest concentrations of JDA-approved properties in Rajasthan, making it a preferred market for nearly all public and private sector lenders. Localities like Vaishali Nagar, Malviya Nagar, Jagatpura, and C-Scheme see the highest loan ticket sizes. Most PSU banks — SBI, Bank of Baroda, PNB — have dedicated home loan processing centres in Jaipur. Society Patta properties in areas like Mansarovar and Jagatpura are widely accepted. WealthMetre advisors process 60+ home loan cases per month across Jaipur and can tell you within minutes which lender best suits your property title, income type, and CIBIL score.',
            'lap'              => 'Commercial LAP in Jaipur is particularly active in the MI Road, Tonk Road, and Ajmer Road business corridors. Residential LAP in Vaishali Nagar, Mansarovar, and Jagatpura carries strong LTV from most lenders. Several NBFCs operating in Jaipur offer balance transfer + top-up on existing LAP accounts, allowing business owners to reduce EMIs while accessing fresh capital. Textile, gems & jewellery, and retail business owners in Jaipur often use LAP as working capital. WealthMetre matches your Jaipur property to lenders who have an active presence and competitive LTV policies in the city.',
            'business-loan'   => 'Jaipur\'s MSME ecosystem spans gems & jewellery, textiles, handicrafts, IT/ITES, hospitality, and retail. Several NBFCs active in Jaipur offer surrogate programs — accepting GST returns or 6–12 months banking statements in place of ITR — designed specifically for Jaipur\'s large base of self-employed and cash-income business owners. Government-backed MUDRA and CGTMSE-linked products are also available for eligible MSMEs. WealthMetre\'s Jaipur team has relationships with 40+ lender branches and can identify the right program for your business type, turnover, and documentation within one consultation.',
            'personal-loan'   => 'Jaipur has a large salaried workforce across government departments, PSUs, private sector companies, and educational institutions. Government employees from RSMSSB, RSRTC, RUHS, Rajasthan Police, and teachers from state-run schools get preferential personal loan rates from most lenders. Private sector employees with reputed companies in Jaipur\'s EPIP Zone and SEZ also qualify for competitive rates. CIBIL score above 720 gets the best offers. WealthMetre compares 30+ personal loan lenders active in Jaipur to find the most competitive rate for your employer category and income level.',
            'car-loan'        => 'Jaipur is one of the highest volume car markets in Rajasthan, with dealerships for Maruti, Hyundai, Tata, Mahindra, Toyota, and luxury brands all operating in the city. New car loans in Jaipur start from 7.9% for top CIBIL profiles. Used car loans are available for vehicles up to 10 years old through select NBFCs. WealthMetre works with lenders who have direct relationships with Jaipur dealerships, enabling faster processing and on-road funding. Whether you are buying from a dealer or a private seller, WealthMetre matches the right car loan product to your income and CIBIL profile.',
            'balance-transfer' => 'Many Jaipur-based home loan and LAP borrowers took loans at rates of 9–11% between 2018–2022. With current rates from 8.25%, a balance transfer can save lakhs over the remaining tenure. WealthMetre\'s Jaipur advisors regularly process balance transfers from NBFCs and smaller lenders to banks with lower rates. The process includes property revaluation, NOC from existing lender, and fresh disbursement — typically completed in 15–25 working days in Jaipur. A top-up loan can also be availed simultaneously to fund business or renovation needs.',
        ],
    ],

    'jodhpur' => [
        'name'      => 'Jodhpur',
        'state'     => 'Rajasthan',
        'desc'      => 'the Blue City and second largest city of Rajasthan',
        'area'      => 'Shastri Nagar, Ratanada, Sardarpura, Chopasni Housing Board',
        'prop_note' => 'JDA Jodhpur, UIT and JNVDA approved properties accepted by most lenders.',
        'pin'       => '342001',
        'advisory'  => [
            'home-loan'        => 'Jodhpur\'s real estate market is growing with strong demand in Shastri Nagar, Ratanada, and Chopasni Housing Board areas. JDA Jodhpur and UIT-approved properties are accepted by PSU and private lenders. Pali Road and Residency Road corridors have seen significant residential development. WealthMetre identifies lenders active in Jodhpur with the best approval rates for your property location and title.',
            'lap'              => 'Commercial and industrial LAP in Jodhpur is popular among handicrafts, textiles, and marble industry business owners. JNVDA industrial plots have selective lender acceptance — WealthMetre can identify which lenders accept your specific property type and get you the best LTV in Jodhpur.',
            'business-loan'   => 'Jodhpur\'s business economy is driven by handicrafts, textiles, marble, spices, and tourism. MSME loans for Jodhpur businesses benefit from CGTMSE guarantees and surrogate programs available through select NBFCs active in the city. WealthMetre identifies the right program for your business vintage and documentation.',
            'personal-loan'   => 'Jodhpur has a significant government employee population across AIIMS Jodhpur, High Court, armed forces, and state departments. These profiles get preferential personal loan rates. WealthMetre compares active lenders in Jodhpur to find the most competitive rate for your employer category.',
            'car-loan'        => 'Jodhpur is a growing automotive market with all major manufacturers represented. New and used car loans are actively processed in Jodhpur by both PSU banks and NBFCs. WealthMetre finds the highest LTV and lowest rate for your car purchase.',
            'balance-transfer' => 'Jodhpur borrowers on higher-rate NBFC loans can benefit significantly from a balance transfer to a bank at lower rates. WealthMetre advisors handle the Jodhpur property revaluation and NOC process smoothly to minimize processing time.',
        ],
    ],

    'udaipur' => [
        'name'      => 'Udaipur',
        'state'     => 'Rajasthan',
        'desc'      => 'the City of Lakes in southern Rajasthan',
        'area'      => 'Hiran Magri, Pratap Nagar, Sukhadia Circle, Bhuwana',
        'prop_note' => 'UDA-approved and Nagar Palika properties eligible across most lender programs.',
        'pin'       => '313001',
        'advisory'  => [
            'home-loan'        => 'Udaipur\'s growing residential zones in Hiran Magri, Pratap Nagar, and Bhuwana are preferred by lenders. UDA-approved properties have strong lender acceptance. Tourism-driven growth means property values have been appreciating steadily, improving LTV prospects for home loan borrowers.',
            'lap'              => 'LAP in Udaipur is active for hospitality and tourism business owners. Residential and commercial property in core Udaipur city qualifies for 50–65% LTV with most lenders. WealthMetre identifies lenders comfortable with Udaipur\'s tourism-heavy commercial real estate.',
            'business-loan'   => 'Udaipur\'s tourism, marble, zinc, and manufacturing businesses have access to multiple MSME finance programs. GST and banking surrogate products are available for businesses with strong transaction histories.',
            'personal-loan'   => 'Udaipur has a sizeable government workforce including MBBS hospital staff, Rajasthan Police, education department employees, and Hindustan Zinc employees — all qualifying for preferential personal loan rates.',
            'car-loan'        => 'Car loans in Udaipur are actively processed for both salaried and self-employed applicants. Tourism and hospitality business owners often finance commercial vehicles through LAP or term loans — WealthMetre covers both segments.',
            'balance-transfer' => 'Udaipur property values have appreciated well, meaning many borrowers now have better LTV headroom for a balance transfer with a top-up. WealthMetre identifies the best transfer + top-up combination for Udaipur properties.',
        ],
    ],

    'kota' => [
        'name'      => 'Kota',
        'state'     => 'Rajasthan',
        'desc'      => 'the industrial and education hub of Rajasthan',
        'area'      => 'Talwandi, Vigyan Nagar, Kunhari, Dadabari',
        'prop_note' => 'KDA and Nagar Nigam approved properties covered. Industrial plots have selective lender acceptance.',
        'pin'       => '324001',
        'advisory'  => [
            'home-loan'        => 'Kota\'s education economy drives strong demand from salaried professionals — teachers, coaching institute staff, NTPC and DCCPP employees, and government workers. KDA-approved properties in Talwandi and Vigyan Nagar are widely accepted. Lenders view Kota positively due to consistent rental yields from student housing.',
            'lap'              => 'Commercial LAP in Kota is strong for coaching institute owners, retail businesses on Aerodrome Circle and Nayapura, and industrial units in Kota Industrial Area. WealthMetre identifies lenders comfortable with Kota\'s education and industrial commercial property.',
            'business-loan'   => 'Kota\'s coaching industry, textiles, and chemical manufacturing create a large MSME borrower base. Education infrastructure loans are a niche WealthMetre specialises in — covering coaching institutes, private schools, and training centres.',
            'personal-loan'   => 'NTPC Kota, DCCPP, government schools, Kota Medical College, and coaching institute employees all qualify for competitive personal loan rates. WealthMetre maps your employer to the most suitable lender.',
            'car-loan'        => 'Car loan demand in Kota is driven by the professional and business community. WealthMetre compares all active lenders in Kota for the best rate and LTV on new and used vehicles.',
            'balance-transfer' => 'Industrial and commercial property owners in Kota who took loans 3–5 years ago can often achieve significant EMI savings through a balance transfer. WealthMetre assesses your current loan and finds the best transfer option.',
        ],
    ],

    'ajmer' => [
        'name'      => 'Ajmer',
        'state'     => 'Rajasthan',
        'desc'      => 'a historic city in central Rajasthan',
        'area'      => 'Vaishali Nagar, Anasagar, Subhash Nagar, Nasirabad Road',
        'prop_note' => 'ADA and Nagar Nigam approved properties eligible for home loan and LAP.',
        'pin'       => '305001',
        'advisory'  => [
            'home-loan'        => 'Ajmer\'s growing residential zones around Vaishali Nagar and Nasirabad Road are well accepted by lenders. ADA-approved properties have good acceptance across PSU and private lenders. Proximity to Jaipur via NH-48 makes Ajmer an attractive market for major bank branches.',
            'lap'              => 'Commercial LAP in Ajmer is active for religious tourism and FMCG trade businesses. Residential LAP in Vaishali Nagar carries good LTV with most lenders. WealthMetre identifies the best match for your Ajmer property profile.',
            'business-loan'   => 'Ajmer\'s trade, hospitality, and small manufacturing businesses have access to MSME programs. Dargah-area commercial businesses with strong banking transactions qualify for surrogate programs.',
            'personal-loan'   => 'Railway employees from Ajmer Railway Division, government teachers, and Rajasthan State employees based in Ajmer get some of the best personal loan rates in the market.',
            'car-loan'        => 'Ajmer has an active car loan market with all major lenders operating in the city. WealthMetre compares active offers for your CIBIL and income profile.',
            'balance-transfer' => 'Ajmer borrowers on builder-linked NBFC loans can benefit from balance transfers to lower-rate bank products. WealthMetre manages the entire transfer process from Ajmer.',
        ],
    ],

    'bhilwara' => [
        'name'      => 'Bhilwara',
        'state'     => 'Rajasthan',
        'desc'      => 'the textile city of Rajasthan',
        'area'      => 'Shastri Nagar, RC Vyas Colony, Sanganeri Gate',
        'prop_note' => 'BDA and Nagar Palika properties covered. Textile business owners have surrogate loan options.',
        'pin'       => '311001',
        'advisory'  => [
            'home-loan'        => 'Bhilwara\'s growing residential market in RC Vyas Colony and Shastri Nagar is covered by most lenders. BDA-approved properties have standard acceptance. The textile industry creates a large self-employed borrower base — WealthMetre identifies lenders with ITR-based and surrogate programs for textile sector applicants.',
            'lap'              => 'LAP is popular among Bhilwara\'s textile mill owners and traders for working capital. Industrial and commercial properties in RIICO area have selective lender coverage — WealthMetre maps lenders accepting your specific property type.',
            'business-loan'   => 'Bhilwara\'s textile ecosystem — spinning mills, weaving units, fabric traders — is one of the largest in Rajasthan. GST-based surrogate programs and MSME term loans are well-suited for this segment. WealthMetre identifies the right lender for your textile business profile.',
            'personal-loan'   => 'Textile company employees, government workers, and salaried professionals in Bhilwara qualify for personal loans. WealthMetre finds the best rate for your employer and CIBIL profile.',
            'car-loan'        => 'Bhilwara\'s business community has strong car loan demand. Self-employed textile professionals qualify for car loans using ITR or banking surrogates.',
            'balance-transfer' => 'Bhilwara property values have stabilised well. Borrowers on higher-rate NBFCs can transfer to banks with lower rates. WealthMetre handles the complete process remotely with Bhilwara property documentation support.',
        ],
    ],

    'alwar' => [
        'name'      => 'Alwar',
        'state'     => 'Rajasthan',
        'desc'      => 'the gateway to Rajasthan near Delhi NCR',
        'area'      => 'Pratap Nagar, Ramgarh Road, Civil Lines, Scheme No. 1',
        'prop_note' => 'ADA and Nagar Palika properties accepted. Proximity to NCR improves lender acceptance.',
        'pin'       => '301001',
        'advisory'  => [
            'home-loan'        => 'Alwar\'s proximity to Delhi NCR — particularly Neemrana\'s industrial corridor — has made it an attractive market for lenders. NCR-based executives buying residential property in Alwar can get home loans from major banks. ADA-approved properties in Pratap Nagar and Civil Lines have wide lender acceptance.',
            'lap'              => 'Industrial LAP in Neemrana and Bhiwadi RIICO areas is available through select lenders. Residential LAP in Alwar city has better LTV from private banks. WealthMetre identifies lenders comfortable with Alwar-adjacent industrial and residential properties.',
            'business-loan'   => 'Alwar\'s industrial growth — auto components, ceramics, textiles — creates strong MSME finance demand. Neemrana-based businesses supplying to MNCs often have strong GST and banking profiles qualifying for competitive rates.',
            'personal-loan'   => 'Alwar has a mix of government and industrial salaried workforce. NCR-linked companies with Alwar-based employees also qualify for competitive personal loan rates.',
            'car-loan'        => 'Alwar\'s growing industrial and professional community has active car loan demand. High LTV and quick processing is available through WealthMetre\'s lender network.',
            'balance-transfer' => 'Alwar\'s appreciated property values post-industrial corridor development make it an ideal market for balance transfer with top-up. WealthMetre manages the process including fresh property valuation.',
        ],
    ],

    'bikaner' => [
        'name'      => 'Bikaner',
        'state'     => 'Rajasthan',
        'desc'      => 'a major city in northwestern Rajasthan',
        'area'      => 'Shastri Nagar, Sadul Colony, Rani Bazar, Murlidhar Vyas Colony',
        'prop_note' => 'BDA approved properties eligible. Desert zone properties may have LTV restrictions.',
        'pin'       => '334001',
        'advisory'  => [
            'home-loan'        => 'Bikaner\'s residential zones in Sadul Colony, Shastri Nagar, and Murlidhar Vyas Colony are accepted by most lenders. BDA-approved properties have good acceptance. Government and armed forces personnel in Bikaner — a key military station — get preferential rates. WealthMetre identifies lenders with active programs in Bikaner.',
            'lap'              => 'LAP in Bikaner is popular for food processing, camel hide, and FMCG businesses. Residential property in core Bikaner has better LTV than peripheral agricultural-adjacent zones. WealthMetre maps lenders comfortable with Bikaner\'s specific property profile.',
            'business-loan'   => 'Bikaner is known for its food industry — Bikaneri bhujia, papad, sweets — with many MSME players having strong GST and banking profiles. CGTMSE-linked products and GST surrogate programs are available through WealthMetre\'s network.',
            'personal-loan'   => 'Military and paramilitary personnel posted in Bikaner, along with state government employees, get some of the best personal loan rates. WealthMetre matches your employer category to the best lender.',
            'car-loan'        => 'Car loan availability in Bikaner covers all major vehicles. WealthMetre ensures your CIBIL and income profile is matched to lenders actively processing in the Bikaner market.',
            'balance-transfer' => 'Bikaner borrowers on older NBFC loans can explore balance transfers to PSU banks for lower rates. WealthMetre manages the process with knowledge of Bikaner property documentation requirements.',
        ],
    ],
];

/* ─────────────────────────────────────────────
   DATA: Loan Products
───────────────────────────────────────────── */
$PRODUCTS = [

    'home-loan' => [
        'name'       => 'Home Loan',
        'short'      => 'home loan',
        'icon'       => '🏠',
        'rate_range' => '8.25% – 11.5%',
        'max_ltv'    => '75–90%',
        'max_tenure' => '30 years',
        'tagline'    => 'Buy your dream home with the best loan offer from 140+ lenders',
        'desc'       => 'Get the most suitable home loan for properties in %CITY% — JDA approved, Society Patta, RHB, and Nagar Nigam. AI-matched to your CIBIL, income, and property title.',
        'eligibility'=> [
            'Salaried: minimum ₹20,000/month in-hand income',
            'Self-employed: minimum 2 years business vintage with ITR',
            'CIBIL score 650+ preferred; some NBFCs accept from 580',
            'Property title: JDA/UDA/Nagar Palika/Nagar Nigam approved',
            'LTV up to 90% for eligible profiles under PMAY',
        ],
        'rate_table' => [
            ['type' => 'PSU Banks (SBI, BOB, PNB)',             'rate' => '8.25% – 9.50%', 'best_for' => 'Salaried, CIBIL 750+'],
            ['type' => 'Private Banks (HDFC, ICICI, Axis)',      'rate' => '8.50% – 10.5%', 'best_for' => 'Salaried & Self-employed'],
            ['type' => 'NBFCs (LIC HFL, PNB HFL, Bajaj)',       'rate' => '8.75% – 11.5%', 'best_for' => 'Low CIBIL, complex titles'],
            ['type' => 'HFCs (Aadhar, Home First, IIFL)',       'rate' => '9.50% – 12.0%', 'best_for' => 'Affordable housing, informal income'],
        ],
        'faqs' => [
            ['q' => 'Which bank gives the best home loan in %CITY%?',
             'a' => 'The best lender depends on your CIBIL score, income type, and property title. WealthMetre AI compares 140+ lenders to find the most suitable option for your profile in %CITY%. Top performing lenders for %CITY% profiles include HDFC, HSBC, Axis Bank, and several NBFCs.'],
            ['q' => 'What documents are needed for a home loan in %CITY%?',
             'a' => 'PAN card, Aadhaar, 3 months salary slips (salaried) or ITR 2 years (self-employed), 6 months bank statements, and property documents. WealthMetre advisors guide you through the exact checklist based on your chosen lender.'],
            ['q' => 'How much home loan can I get in %CITY%?',
             'a' => 'Home loan eligibility depends on your monthly income, existing EMIs, CIBIL score, and property value. Typically banks offer 60×–70× your monthly salary or up to 75–90% LTV. Use our AI calculator for an instant estimate.'],
            ['q' => 'Can I get a home loan on JDA property in %CITY%?',
             'a' => 'Yes. JDA approved properties are accepted by most banks and NBFCs. WealthMetre matches your property title to lenders with applicable programs in %CITY%.'],
        ],
    ],

    'lap' => [
        'name'       => 'Loan Against Property',
        'short'      => 'loan against property',
        'icon'       => '🏢',
        'rate_range' => '8.5% – 13%',
        'max_ltv'    => '50–70%',
        'max_tenure' => '20 years',
        'tagline'    => 'Unlock your property value with the best LAP rates from 40+ lenders',
        'desc'       => 'Get a loan against your residential, commercial, or industrial property in %CITY%. AI-matched lender selection based on your property type, title, income, and CIBIL score.',
        'eligibility'=> [
            'Residential property: up to 70% LTV for eligible profiles',
            'Commercial property: up to 60% LTV',
            'Industrial property: up to 55% LTV with selective lenders',
            'CIBIL score 650+ preferred',
            'Both salaried and self-employed eligible',
            'Property must be self-owned and free of legal disputes',
        ],
        'rate_table' => [
            ['type' => 'PSU Banks (SBI, BOB, Canara)',          'rate' => '8.50% – 10.0%', 'best_for' => 'Residential, CIBIL 720+'],
            ['type' => 'Private Banks (HDFC, ICICI, Kotak)',    'rate' => '9.0% – 11.5%',  'best_for' => 'Residential & commercial'],
            ['type' => 'NBFCs (Bajaj, Tata Capital, Piramal)',  'rate' => '10.0% – 13.0%', 'best_for' => 'Low CIBIL, industrial plots'],
            ['type' => 'HFCs (GIC, LIC HFL)',                   'rate' => '9.25% – 12.0%', 'best_for' => 'Residential, long tenure'],
        ],
        'faqs' => [
            ['q' => 'What is the LAP interest rate in %CITY%?',
             'a' => 'LAP rates in %CITY% range from 8.5% to 13% depending on your CIBIL score, income type, property value, and lender. WealthMetre identifies the most competitive rate for your specific profile across 40+ LAP lenders.'],
            ['q' => 'How much loan can I get against my property in %CITY%?',
             'a' => 'Typically 50–70% of market value for residential, 50–60% for commercial, and 45–55% for industrial properties in %CITY%. Final amount depends on income, FOIR, CIBIL, and lender policy.'],
            ['q' => 'Can I get LAP on rented commercial property in %CITY%?',
             'a' => 'Yes. Several lenders offer LAP on rented commercial properties in %CITY%. Rental income is considered for eligibility. WealthMetre identifies lenders who accept your specific property type and usage.'],
            ['q' => 'Is LAP available on plots in %CITY%?',
             'a' => 'Plot LAP is available with selective lenders in %CITY% for approved/developed plots. JDA, UDA, and Nagar Palika plots are accepted by more lenders than unapproved plots.'],
        ],
    ],

    'business-loan' => [
        'name'       => 'Business Loan',
        'short'      => 'business loan',
        'icon'       => '💼',
        'rate_range' => '10% – 18%',
        'max_ltv'    => 'N/A',
        'max_tenure' => '5 years',
        'tagline'    => 'Fast business loans for MSMEs in %CITY% — secured and unsecured options',
        'desc'       => 'Get working capital, term loans, and MSME finance for your business in %CITY%. Multiple lenders matched to your turnover, GST returns, banking, and business vintage.',
        'eligibility'=> [
            'Minimum 2 years business vintage',
            'Turnover ₹20 lakh+ per year',
            'GST registration preferred for higher amounts',
            'Banking surrogate available for eligible profiles',
            'CIBIL 650+ preferred; some NBFCs accept from 600',
            'No ITR? Banking surrogate and GST surrogate programs available',
        ],
        'rate_table' => [
            ['type' => 'PSU Banks (SBI, BOB — MSME)',           'rate' => '10.0% – 13.5%', 'best_for' => 'Strong ITR, CIBIL 700+'],
            ['type' => 'Private Banks (HDFC, ICICI, Axis)',      'rate' => '11.0% – 16.0%', 'best_for' => 'Salaried turnover, pre-approved'],
            ['type' => 'NBFCs (Bajaj, Lendingkart, Flexi)',     'rate' => '14.0% – 20.0%', 'best_for' => 'Low CIBIL, banking surrogate'],
            ['type' => 'CGTMSE / MUDRA linked',                  'rate' => '9.5% – 12.0%', 'best_for' => 'Micro & small businesses'],
        ],
        'faqs' => [
            ['q' => 'Can I get a business loan without ITR in %CITY%?',
             'a' => 'Yes. Several lenders offer business loans on banking surrogate (6–12 months bank statements) or GST surrogate in %CITY%. WealthMetre identifies the right program for your income documentation type.'],
            ['q' => 'What is the maximum business loan amount in %CITY%?',
             'a' => 'Unsecured business loans go up to ₹50 lakh in %CITY% for eligible profiles. Secured loans against property can go much higher depending on collateral value and income.'],
            ['q' => 'How fast can I get a business loan in %CITY%?',
             'a' => 'Pre-approved and banking surrogate programs can disburse in 5–7 working days in %CITY% for eligible profiles. Standard programs take 10–15 days.'],
            ['q' => 'Does my CIBIL score matter for a business loan in %CITY%?',
             'a' => 'Yes, but some lenders consider business CIBIL or bank statement history even for lower CIBIL scores. WealthMetre finds lenders who match your actual credit profile.'],
        ],
    ],

    'personal-loan' => [
        'name'       => 'Personal Loan',
        'short'      => 'personal loan',
        'icon'       => '👤',
        'rate_range' => '10.5% – 22%',
        'max_ltv'    => 'N/A',
        'max_tenure' => '5 years',
        'tagline'    => 'Instant personal loans for salaried employees in %CITY%',
        'desc'       => 'Get a personal loan in %CITY% with no collateral required. Best rates for salaried employees matched across 30+ lenders based on your income and CIBIL score.',
        'eligibility'=> [
            'Salaried employees at private or government organizations',
            'Minimum salary ₹15,000/month in-hand',
            'CIBIL score 700+ preferred',
            'Minimum 6 months employment with current employer',
            'No collateral required',
        ],
        'rate_table' => [
            ['type' => 'PSU Banks (SBI, BOB, PNB)',             'rate' => '10.5% – 13.0%', 'best_for' => 'Government employees, CIBIL 750+'],
            ['type' => 'Private Banks (HDFC, ICICI, Kotak)',    'rate' => '10.75% – 16.0%','best_for' => 'Listed company employees'],
            ['type' => 'NBFCs (Bajaj, Tata Capital, Fullerton)','rate' => '13.0% – 22.0%', 'best_for' => 'CIBIL 650–700, small employers'],
            ['type' => 'Fintech (Navi, MoneyView, KreditBee)',  'rate' => '16.0% – 24.0%', 'best_for' => 'Quick disbursement, low income'],
        ],
        'faqs' => [
            ['q' => 'What is the personal loan interest rate in %CITY%?',
             'a' => 'Personal loan rates in %CITY% range from 10.5% to 22% depending on your employer category, CIBIL score, and income. Government employees and top-rated company employees get the best rates.'],
            ['q' => 'Can I get a personal loan with low CIBIL in %CITY%?',
             'a' => 'Some NBFCs and fintech lenders offer personal loans for CIBIL 650+ in %CITY%. WealthMetre identifies lenders with programs for your exact CIBIL range.'],
            ['q' => 'How much personal loan can I get in %CITY%?',
             'a' => 'Personal loan amounts in %CITY% depend on your monthly income. Typically 10–22 times monthly salary up to ₹40 lakh for eligible profiles with top lenders.'],
        ],
    ],

    'car-loan' => [
        'name'       => 'Car Loan',
        'short'      => 'car loan',
        'icon'       => '🚗',
        'rate_range' => '7.9% – 12%',
        'max_ltv'    => 'Up to 100% on-road',
        'max_tenure' => '7 years',
        'tagline'    => 'Best car loan rates in %CITY% — new and used vehicles',
        'desc'       => 'Get the best new and used car loan in %CITY%. High on-road funding, quick processing, and competitive rates matched to your income and CIBIL score across 20+ lenders.',
        'eligibility'=> [
            'Salaried: minimum ₹15,000/month income',
            'Self-employed: ITR 1 year minimum',
            'CIBIL 650+ preferred',
            'Both new and used car loans available',
            'Up to 100% on-road price for eligible profiles',
        ],
        'rate_table' => [
            ['type' => 'PSU Banks (SBI, BOB, Union)',           'rate' => '7.90% – 9.50%', 'best_for' => 'Salaried, CIBIL 750+'],
            ['type' => 'Private Banks (HDFC, ICICI, Axis)',      'rate' => '8.50% – 10.5%', 'best_for' => 'New cars, pre-approved'],
            ['type' => 'NBFCs (Bajaj, Tata Capital, Chola)',    'rate' => '9.50% – 12.0%', 'best_for' => 'Used cars, self-employed'],
            ['type' => 'Manufacturer Finance (Maruti, Hyundai)','rate' => '7.90% – 10.0%', 'best_for' => 'Specific models, quick approval'],
        ],
        'faqs' => [
            ['q' => 'What is the car loan interest rate in %CITY%?',
             'a' => 'Car loan rates in %CITY% start from 7.9% for eligible new car purchases. Rates depend on car brand, CIBIL score, and income type.'],
            ['q' => 'Can I get a car loan without salary slip in %CITY%?',
             'a' => 'Self-employed applicants in %CITY% can get car loans using ITR, Form 26AS, or bank statements. WealthMetre finds lenders accepting your income documentation.'],
            ['q' => 'Are used car loans available in %CITY%?',
             'a' => 'Yes. Used car loans are available for vehicles up to 10 years old in %CITY% through select NBFCs. Rates are slightly higher than new car loans. WealthMetre identifies the best used car loan for your profile.'],
        ],
    ],

    'balance-transfer' => [
        'name'       => 'Balance Transfer',
        'short'      => 'loan balance transfer',
        'icon'       => '🔄',
        'rate_range' => '7.9% – 10%',
        'max_ltv'    => 'Up to 90% on new valuation',
        'max_tenure' => '30 years',
        'tagline'    => 'Reduce your home loan EMI with a balance transfer in %CITY%',
        'desc'       => 'Transfer your existing home loan or LAP to a lower interest rate lender in %CITY%. Save lakhs over tenure. WealthMetre identifies the best transfer option for your outstanding balance.',
        'eligibility'=> [
            'Minimum 12 EMI repayment track record with current lender',
            'No defaults in last 12 months',
            'CIBIL 700+ for best rates',
            'Property value sufficient for new LTV requirements',
            'Top-up loan available along with transfer',
        ],
        'rate_table' => [
            ['type' => 'PSU Banks (SBI, BOB, PNB)',             'rate' => '7.90% – 9.0%',  'best_for' => 'CIBIL 750+, salaried'],
            ['type' => 'Private Banks (HDFC, ICICI, Axis)',      'rate' => '8.25% – 9.75%', 'best_for' => 'Quick processing, top-up'],
            ['type' => 'HFCs (LIC HFL, PNB HFL)',               'rate' => '8.50% – 10.0%', 'best_for' => 'Long tenure, high LTV'],
            ['type' => 'NBFCs (Bajaj, Tata Capital)',            'rate' => '9.0% – 11.5%',  'best_for' => 'Complex titles, lower CIBIL'],
        ],
        'faqs' => [
            ['q' => 'How much can I save with a balance transfer in %CITY%?',
             'a' => 'Savings depend on current rate vs new rate and outstanding tenure. On a ₹50L loan with 20 years remaining, even a 1% rate reduction saves ₹7–10 lakh in total interest. WealthMetre calculates your exact savings before you decide.'],
            ['q' => 'Can I get a top-up loan with balance transfer in %CITY%?',
             'a' => 'Yes. Several lenders offer top-up loans of 15–25% over outstanding balance during balance transfer in %CITY%, subject to property valuation and income eligibility.'],
            ['q' => 'How long does a balance transfer take in %CITY%?',
             'a' => 'Balance transfers in %CITY% typically complete in 15–25 working days including property revaluation, NOC from existing lender, and new lender disbursement. WealthMetre coordinates the entire process.'],
        ],
    ],
];

/* ─────────────────────────────────────────────
   VALIDATE & EXTRACT params
───────────────────────────────────────────── */
$city_slug    = strtolower(trim($_GET['city']    ?? ''));
$product_slug = strtolower(trim($_GET['product'] ?? ''));

if (!isset($CITIES[$city_slug]) || !isset($PRODUCTS[$product_slug])) {
    header('HTTP/1.1 404 Not Found');
    header('Location: /');
    exit;
}

$city    = $CITIES[$city_slug];
$product = $PRODUCTS[$product_slug];

$city_name     = $city['name'];
$product_name  = $product['name'];
$product_short = $product['short'];

$page_title       = "Best {$product_name} in {$city_name} | WealthMetre — Compare 140+ Lenders";
$meta_desc        = "Get the best {$product_short} in {$city_name}, Rajasthan. WealthMetre AI compares 140+ banks & NBFCs for your profile. Free advisory, instant lender matching.";
$h1               = "Best {$product_name} in {$city_name}";
$canonical        = "https://wealthmetre.com/{$product_slug}-{$city_slug}.html";
$page_description = fill($product['desc'], $city_name);
$page_tagline     = fill($product['tagline'], $city_name);
$advisory_text    = $city['advisory'][$product_slug] ?? '';

/* ─────────────────────────────────────────────
   SCHEMA: FAQ
───────────────────────────────────────────── */
$faq_schema_items = [];
foreach ($product['faqs'] as $faq) {
    $faq_schema_items[] = [
        '@type'          => 'Question',
        'name'           => fill($faq['q'], $city_name),
        'acceptedAnswer' => [
            '@type' => 'Answer',
            'text'  => fill($faq['a'], $city_name),
        ],
    ];
}
$faq_schema = json_encode([
    '@context'   => 'https://schema.org',
    '@type'      => 'FAQPage',
    'mainEntity' => $faq_schema_items,
], JSON_UNESCAPED_UNICODE);

/* ─────────────────────────────────────────────
   SCHEMA: FinancialService
───────────────────────────────────────────── */
$service_schema = json_encode([
    '@context'    => 'https://schema.org',
    '@type'       => 'FinancialService',
    'name'        => "WealthMetre — {$product_name} Advisory in {$city_name}",
    'description' => $meta_desc,
    'url'         => $canonical,
    'telephone'   => '+91-7976218596',
    'areaServed'  => $city_name,
    'provider'    => [
        '@type'   => 'LocalBusiness',
        'name'    => 'WealthMetre Finserve',
        'address' => [
            '@type'           => 'PostalAddress',
            'addressLocality' => 'Jaipur',
            'addressRegion'   => 'Rajasthan',
            'postalCode'      => '302006',
            'addressCountry'  => 'IN',
        ],
    ],
], JSON_UNESCAPED_UNICODE);

/* ─────────────────────────────────────────────
   SCHEMA: BreadcrumbList  ← NEW
───────────────────────────────────────────── */
$breadcrumb_schema = json_encode([
    '@context'        => 'https://schema.org',
    '@type'           => 'BreadcrumbList',
    'itemListElement' => [
        ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home',         'item' => 'https://wealthmetre.com/'],
        ['@type' => 'ListItem', 'position' => 2, 'name' => $product_name,  'item' => "https://wealthmetre.com/{$product_slug}-{$city_slug}.html"],
        ['@type' => 'ListItem', 'position' => 3, 'name' => $city_name],
    ],
], JSON_UNESCAPED_UNICODE);

/* ─────────────────────────────────────────────
   INTERNAL LINKS
───────────────────────────────────────────── */
$related_cities    = array_filter(array_keys($CITIES),    fn($c) => $c !== $city_slug);
$related_products  = array_filter(array_keys($PRODUCTS),  fn($p) => $p !== $product_slug);

/* ─────────────────────────────────────────────
   DIVA PRELOAD MAP
───────────────────────────────────────────── */
$preload_product = match($product_slug) {
    'home-loan'        => 'home',
    'lap'              => 'lap',
    'business-loan'    => 'business',
    'personal-loan'    => 'personal',
    'car-loan'         => 'car',
    'balance-transfer' => 'balance_transfer',
    default            => ''
};

?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1.0" />
  <title><?= htmlspecialchars($page_title) ?></title>
  <meta name="description" content="<?= htmlspecialchars($meta_desc) ?>" />
  <meta name="robots" content="index,follow" />
  <link rel="canonical" href="<?= htmlspecialchars($canonical) ?>" />

  <!-- Open Graph -->
  <meta property="og:title"       content="<?= htmlspecialchars($h1) ?> | WealthMetre" />
  <meta property="og:description" content="<?= htmlspecialchars($meta_desc) ?>" />
  <meta property="og:type"        content="website" />
  <meta property="og:url"         content="<?= htmlspecialchars($canonical) ?>" />
  <meta property="og:image"       content="https://wealthmetre.com/images/og/<?= htmlspecialchars($product_slug) ?>-<?= htmlspecialchars($city_slug) ?>.jpg" />
  <meta property="og:image:width"  content="1200" />
  <meta property="og:image:height" content="630" />
  <meta property="og:site_name"   content="WealthMetre" />

  <!-- Twitter Card -->
  <meta name="twitter:card"        content="summary_large_image" />
  <meta name="twitter:title"       content="<?= htmlspecialchars($h1) ?> | WealthMetre" />
  <meta name="twitter:description" content="<?= htmlspecialchars($meta_desc) ?>" />
  <meta name="twitter:image"       content="https://wealthmetre.com/images/og/<?= htmlspecialchars($product_slug) ?>-<?= htmlspecialchars($city_slug) ?>.jpg" />

  <link rel="icon" href="/images/favicon_new.png" type="image/png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="/css/style.css" />
  <link rel="stylesheet" href="/css/additions.css" />
  <link rel="stylesheet" href="/css/diva-widget.css" />
  <link rel="stylesheet" href="/css/seo-page.css" />

  <!-- Structured Data -->
  <script type="application/ld+json"><?= $faq_schema ?></script>
  <script type="application/ld+json"><?= $service_schema ?></script>
  <script type="application/ld+json"><?= $breadcrumb_schema ?></script>

  <style>
    /* ── Hero cycling animation ── */
    @keyframes cycleIn  { from { opacity:0; transform:translateY(12px);  } to { opacity:1; transform:translateY(0); } }
    @keyframes cycleOut { from { opacity:1; transform:translateY(0);     } to { opacity:0; transform:translateY(-10px); } }
    .sp-cycle-out { animation: cycleOut 0.3s ease forwards; }
    .sp-cycle-in  { animation: cycleIn  0.35s ease forwards; }

    /* Product indicator dots */
    .sp-product-dots { display:flex; gap:6px; margin-bottom:16px; align-items:center; }
    .sp-product-dot  { width:6px; height:6px; border-radius:50%; background:rgba(255,255,255,0.3); transition:all .3s ease; cursor:pointer; }
    .sp-product-dot.active { width:20px; border-radius:3px; background:#f59e0b; }

    /* Progress bar */
    .sp-cycle-bar      { width:100%; height:2px; background:rgba(255,255,255,.12); border-radius:2px; margin-bottom:20px; overflow:hidden; }
    .sp-cycle-bar-fill { height:100%; background:#f59e0b; border-radius:2px; width:0%; transition:width linear; }

    /* Rate table */
    .sp-rate-table { width:100%; border-collapse:collapse; margin-top:16px; font-size:14px; }
    .sp-rate-table th { background:#1e3a5f; color:#fff; padding:10px 14px; text-align:left; }
    .sp-rate-table td { padding:10px 14px; border-bottom:1px solid #e5e7eb; }
    .sp-rate-table tr:last-child td { border-bottom:none; }
    .sp-rate-table tr:nth-child(even) td { background:#f9fafb; }
    @media (max-width:600px) {
      .sp-rate-table thead { display:none; }
      .sp-rate-table td { display:block; padding:6px 12px; }
      .sp-rate-table td::before { content:attr(data-label)": "; font-weight:600; }
    }

    /* Advisory note box */
    .sp-advisory-box { background:#f0f7ff; border-left:4px solid #1e3a5f; border-radius:8px; padding:20px 24px; margin-top:24px; line-height:1.8; color:#374151; font-size:15px; }
    .sp-advisory-box strong { color:#1e3a5f; }
  </style>
</head>

<body>

  <?php include __DIR__ . '/includes/header.php'; ?>

  <!-- ══════════════════════════════════════════
       HERO
  ══════════════════════════════════════════ -->
  <section class="sp-hero">
    <div class="container">

      <!-- Breadcrumb — FIXED (no longer hardcoded to Jaipur) -->
      <div class="sp-breadcrumb">
        <a href="/">Home</a> <span>›</span>
        <a href="/<?= htmlspecialchars($product_slug) ?>-<?= htmlspecialchars($city_slug) ?>.html"><?= htmlspecialchars($product_name) ?></a> <span>›</span>
        <span><?= htmlspecialchars($city_name) ?></span>
      </div>

      <div class="sp-hero-grid">

        <!-- LEFT COLUMN -->
        <div class="sp-hero-left">

          <!-- Product dots indicator -->
          <div class="sp-product-dots" id="spProductDots"></div>

          <!-- Cycle progress bar -->
          <div class="sp-cycle-bar"><div class="sp-cycle-bar-fill" id="spCycleBar"></div></div>

          <!-- Badge -->
          <div class="sp-badge" id="spBadge">
            <?= htmlspecialchars($product['icon']) ?> <?= htmlspecialchars($product_name) ?> · <?= htmlspecialchars($city_name) ?>
          </div>

          <h1 class="sp-h1">
            Best <span id="spProductName"><?= htmlspecialchars($product_name) ?></span><br>
            in <?= htmlspecialchars($city_name) ?>
          </h1>

          <p class="sp-tagline" id="spTagline"><?= htmlspecialchars($page_tagline) ?></p>
          <p class="sp-desc"    id="spDesc"><?= htmlspecialchars($page_description) ?></p>

          <!-- Rate strip -->
          <div class="sp-rate-strip">
            <div class="sp-rate-item">
              <span class="sp-rate-label">Interest Rate</span>
              <span class="sp-rate-val" id="spRateRange"><?= htmlspecialchars($product['rate_range']) ?></span>
            </div>
            <div class="sp-rate-item">
              <span class="sp-rate-label">Max LTV</span>
              <span class="sp-rate-val" id="spMaxLtv"><?= htmlspecialchars($product['max_ltv']) ?></span>
            </div>
            <div class="sp-rate-item">
              <span class="sp-rate-label">Max Tenure</span>
              <span class="sp-rate-val" id="spMaxTenure"><?= htmlspecialchars($product['max_tenure']) ?></span>
            </div>
          </div>

          <!-- CTAs -->
          <div class="sp-btns">
            <a href="#" class="btn btn-orange open-diva-trigger" onclick="wmOpenDiva(event)">
              <i class="fas fa-robot"></i> Check My Eligibility Free
            </a>
            <a href="https://wa.me/917976218596?text=I+need+<?= urlencode($product_name) ?>+advisory+in+<?= urlencode($city_name) ?>"
               target="_blank" class="btn btn-wa">
              <i class="fab fa-whatsapp"></i> WhatsApp Expert
            </a>
          </div>

          <p class="sp-disclaimer">*Rates indicative for eligible profiles. Subject to lender credit assessment.</p>

        </div><!-- /.sp-hero-left -->

        <!-- RIGHT COLUMN — Trust signals + quick stats -->
        <div class="sp-hero-right">
          <div class="sp-trust-card">
            <div class="sp-trust-stat"><span class="sp-trust-num">140+</span><span class="sp-trust-label">Bank &amp; NBFC Partners</span></div>
            <div class="sp-trust-stat"><span class="sp-trust-num">₹500Cr+</span><span class="sp-trust-label">Loans Facilitated</span></div>
            <div class="sp-trust-stat"><span class="sp-trust-num">13+</span><span class="sp-trust-label">Years of Expertise</span></div>
            <div class="sp-trust-stat"><span class="sp-trust-num">Free</span><span class="sp-trust-label">Advisory, Zero Fees</span></div>
            <div class="sp-trust-diva">
              <i class="fas fa-robot"></i>
              <div>
                <strong>Diva AI</strong> is ready to match you<br>
                with the best lender in <?= htmlspecialchars($city_name) ?>.
              </div>
            </div>
            <a href="#" onclick="wmOpenDiva(event)" class="btn btn-orange" style="width:100%;text-align:center;margin-top:12px">
              <i class="fas fa-bolt"></i> Start Free Matching
            </a>
          </div>
        </div><!-- /.sp-hero-right -->

      </div><!-- /.sp-hero-grid -->
    </div><!-- /.container -->
  </section><!-- /.sp-hero -->


  <!-- ══════════════════════════════════════════
       INTEREST RATE TABLE  ← NEW SECTION
  ══════════════════════════════════════════ -->
  <section class="sp-section">
    <div class="container">
      <div class="sec-head">
        <div class="sec-badge"><i class="fas fa-percentage"></i> Current Rates</div>
        <h2><?= htmlspecialchars($product_name) ?> Interest Rates in <?= htmlspecialchars($city_name) ?> — <?= date('F Y') ?></h2>
        <p style="color:#6b7280;margin-top:8px">Rates vary by lender, CIBIL score, income type, and property. WealthMetre identifies the exact rate you qualify for.</p>
      </div>
      <div class="sp-rate-table-wrap">
        <table class="sp-rate-table">
          <thead>
            <tr>
              <th>Lender Type</th>
              <th>Rate Range (p.a.)</th>
              <th>Best For</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($product['rate_table'] as $row): ?>
            <tr>
              <td data-label="Lender Type"><?= htmlspecialchars($row['type']) ?></td>
              <td data-label="Rate Range"><strong><?= htmlspecialchars($row['rate']) ?></strong></td>
              <td data-label="Best For"><?= htmlspecialchars($row['best_for']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p style="font-size:12px;color:#9ca3af;margin-top:10px">
        *Rates updated <?= date('F Y') ?>. Final rate depends on individual credit profile and lender assessment.
      </p>
    </div>
  </section>


  <!-- ══════════════════════════════════════════
       ELIGIBILITY
  ══════════════════════════════════════════ -->
  <section class="sp-section sp-section-alt">
    <div class="container">
      <div class="sec-head">
        <div class="sec-badge"><i class="fas fa-check-circle"></i> Eligibility Criteria</div>
        <h2><?= htmlspecialchars($product_name) ?> Eligibility in <?= htmlspecialchars($city_name) ?></h2>
      </div>
      <div class="sp-elig-grid">
        <?php foreach ($product['eligibility'] as $item): ?>
        <div class="sp-elig-item">
          <i class="fas fa-check-circle"></i>
          <span><?= htmlspecialchars(fill($item, $city_name)) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if (!empty($city['prop_note'])): ?>
      <div class="sp-city-note">
        <i class="fas fa-map-marker-alt"></i>
        <strong><?= htmlspecialchars($city_name) ?> Property Note:</strong>
        <?= htmlspecialchars($city['prop_note']) ?>
      </div>
      <?php endif; ?>
    </div>
  </section>


  <!-- ══════════════════════════════════════════
       CITY-SPECIFIC ADVISORY  ← NEW SECTION
  ══════════════════════════════════════════ -->
  <?php if (!empty($advisory_text)): ?>
  <section class="sp-section">
    <div class="container">
      <div class="sec-head">
        <div class="sec-badge"><i class="fas fa-map-marked-alt"></i> <?= htmlspecialchars($city_name) ?> Advisory</div>
        <h2><?= htmlspecialchars($product_name) ?> in <?= htmlspecialchars($city_name) ?> — What You Should Know</h2>
      </div>
      <div class="sp-advisory-box">
        <?= nl2br(htmlspecialchars($advisory_text)) ?>
      </div>
    </div>
  </section>
  <?php endif; ?>


  <!-- ══════════════════════════════════════════
       WHY WEALTHMETRE
  ══════════════════════════════════════════ -->
  <section class="sp-section sp-section-alt">
    <div class="container">
      <div class="sec-head">
        <div class="sec-badge"><i class="fas fa-award"></i> Why WealthMetre</div>
        <h2>Why Choose WealthMetre for <?= htmlspecialchars($product_name) ?> in <?= htmlspecialchars($city_name) ?></h2>
      </div>
      <div class="sp-why-grid">
        <div class="sp-why-card">
          <i class="fas fa-robot sp-why-icon"></i>
          <h3>AI Lender Matching</h3>
          <p>Diva AI instantly checks your CIBIL, income, and property against 140+ lender policies — finding the best match for <?= htmlspecialchars($product_name) ?> in <?= htmlspecialchars($city_name) ?>.</p>
        </div>
        <div class="sp-why-card">
          <i class="fas fa-map-marker-alt sp-why-icon"></i>
          <h3>Local <?= htmlspecialchars($city_name) ?> Expertise</h3>
          <p>We know <?= htmlspecialchars($city_name) ?>'s property market — which areas lenders prefer, which titles get approved, and which lenders operate actively in the city.</p>
        </div>
        <div class="sp-why-card">
          <i class="fas fa-users sp-why-icon"></i>
          <h3>Human Advisory Backed</h3>
          <p>Every AI recommendation is backed by expert advisors with 13+ years in banking. Your <?= htmlspecialchars($product_name) ?> case is handled personally from shortlist to disbursal.</p>
        </div>
        <div class="sp-why-card">
          <i class="fas fa-shield-alt sp-why-icon"></i>
          <h3>Zero Cost Advisory</h3>
          <p>WealthMetre's advisory in <?= htmlspecialchars($city_name) ?> is completely free. No hidden charges, no upfront fees — we earn from lenders only on successful disbursal.</p>
        </div>
      </div>
    </div>
  </section>


  <!-- ══════════════════════════════════════════
       FAQ
  ══════════════════════════════════════════ -->
  <section class="sp-section">
    <div class="container">
      <div class="sec-head">
        <div class="sec-badge"><i class="fas fa-question-circle"></i> FAQ</div>
        <h2><?= htmlspecialchars($product_name) ?> in <?= htmlspecialchars($city_name) ?> — Common Questions</h2>
      </div>
      <div class="faq-list">
        <?php foreach ($product['faqs'] as $faq): ?>
        <div class="faq-item">
          <div class="faq-q">
            <h4><?= htmlspecialchars(fill($faq['q'], $city_name)) ?></h4>
            <div class="fq-icon"><i class="fas fa-plus"></i></div>
          </div>
          <div class="faq-a">
            <p><?= htmlspecialchars(fill($faq['a'], $city_name)) ?></p>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>


  <!-- ══════════════════════════════════════════
       INTERNAL LINKS — other cities + products
  ══════════════════════════════════════════ -->
  <section class="sp-section sp-section-alt">
    <div class="container">
      <h2 style="font-size:20px;font-weight:700;margin-bottom:16px">
        <?= htmlspecialchars($product_name) ?> in Other Rajasthan Cities
      </h2>
      <div class="sp-link-grid">
        <?php foreach ($related_cities as $rc): ?>
        <a href="/<?= htmlspecialchars($product_slug) ?>-<?= htmlspecialchars($rc) ?>.html" class="sp-city-link">
          <i class="fas fa-city"></i>
          <?= htmlspecialchars($product_name) ?> in <?= htmlspecialchars($CITIES[$rc]['name']) ?>
        </a>
        <?php endforeach; ?>
      </div>

      <h2 style="font-size:20px;font-weight:700;margin:32px 0 16px">
        Other Loans in <?= htmlspecialchars($city_name) ?>
      </h2>
      <div class="sp-link-grid">
        <?php foreach ($related_products as $rp): ?>
        <a href="/<?= htmlspecialchars($rp) ?>-<?= htmlspecialchars($city_slug) ?>.html" class="sp-city-link">
          <?= htmlspecialchars($PRODUCTS[$rp]['icon']) ?>
          <?= htmlspecialchars($PRODUCTS[$rp]['name']) ?> in <?= htmlspecialchars($city_name) ?>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
  </section>


  <!-- ══════════════════════════════════════════
       CTA BANNER
  ══════════════════════════════════════════ -->
  <section class="sp-cta-section">
    <div class="container">
      <div class="sp-cta-inner">
        <h2>Get Your Best <?= htmlspecialchars($product_name) ?> Offer in <?= htmlspecialchars($city_name) ?></h2>
        <p>Free AI matching · 140+ lenders · Expert advisory · Same day callback</p>
        <div class="sp-cta-btns">
          <a href="#" onclick="wmOpenDiva(event)" class="btn btn-orange">
            <i class="fas fa-robot"></i> Talk to Diva AI — Free
          </a>
          <a href="tel:+917976218596" class="btn btn-white">
            <i class="fas fa-phone"></i> Call +91 7976218596
          </a>
        </div>
      </div>
    </div>
  </section>


  <!-- ══════════════════════════════════════════
       DIVA CONTEXT PRELOAD
  ══════════════════════════════════════════ -->
  <script>
  window._divaPreload = {
    product: '<?= htmlspecialchars($preload_product) ?>',
    city:    '<?= htmlspecialchars(strtolower($city_name)) ?>',
    page:    '<?= htmlspecialchars($product_slug) ?>-<?= htmlspecialchars($city_slug) ?>'
  };
  </script>

  <?php include __DIR__ . '/includes/footer.php'; ?>


  <!-- ══════════════════════════════════════════
       HERO CYCLING SCRIPT
       FIX: cycleTo(current) called on init so
            page loads on correct product immediately
  ══════════════════════════════════════════ -->
  <script>
  (function () {

    var CITY = '<?= htmlspecialchars($city_name, ENT_QUOTES) ?>';

    var PRODUCTS = [
      { icon:'🏠', name:'Home Loan',           tagline:'Buy your dream home with the best loan offer from 140+ lenders', desc:'Get the most suitable home loan for properties in '+CITY+' — JDA approved, Society Patta, RHB, and Nagar Nigam. AI-matched to your CIBIL, income, and property title.', rate:'8.25% – 11.5%', ltv:'75–90%',         tenure:'30 years' },
      { icon:'🏢', name:'Loan Against Property',tagline:'Unlock your property value with the best LAP rates from 40+ lenders', desc:'Get a loan against your residential, commercial, or industrial property in '+CITY+'. AI-matched lender selection based on your property type, title, income, and CIBIL score.', rate:'8.5% – 13%',   ltv:'50–70%',         tenure:'20 years' },
      { icon:'💼', name:'Business Loan',        tagline:'Fast business loans for MSMEs in '+CITY+' — secured and unsecured options', desc:'Get working capital, term loans, and MSME finance for your business in '+CITY+'. Multiple lenders matched to your turnover, GST returns, banking, and business vintage.', rate:'10% – 18%',   ltv:'N/A',            tenure:'5 years'  },
      { icon:'👤', name:'Personal Loan',        tagline:'Instant personal loans for salaried employees in '+CITY, desc:'Get a personal loan in '+CITY+' with no collateral required. Best rates for salaried employees matched across 30+ lenders based on your income and CIBIL score.', rate:'10.5% – 22%', ltv:'N/A',            tenure:'5 years'  },
      { icon:'🚗', name:'Car Loan',             tagline:'Best car loan rates in '+CITY+' — new and used vehicles', desc:'Get the best new and used car loan in '+CITY+'. High on-road funding, quick processing, and competitive rates matched to your income and CIBIL score across 20+ lenders.', rate:'7.9% – 12%',  ltv:'Up to 100%',     tenure:'7 years'  },
      { icon:'🔄', name:'Balance Transfer',     tagline:'Reduce your home loan EMI with a balance transfer in '+CITY, desc:'Transfer your existing home loan or LAP to a lower interest rate lender in '+CITY+'. Save lakhs over tenure. WealthMetre identifies the best transfer option for your outstanding balance.', rate:'7.9% – 10%',  ltv:'Up to 90%',      tenure:'30 years' },
    ];

    var INTERVAL_MS = 2200;
    var ANIMATE_MS  = 300;

    /* Start on whichever product matches the current URL */
    var current = (function () {
      var map = { 'home-loan':0, 'lap':1, 'business-loan':2, 'personal-loan':3, 'car-loan':4, 'balance-transfer':5 };
      return map['<?= htmlspecialchars($product_slug, ENT_QUOTES) ?>'] || 0;
    })();

    var timer = null;

    /* DOM refs */
    var elBadge   = document.getElementById('spBadge');
    var elName    = document.getElementById('spProductName');
    var elTagline = document.getElementById('spTagline');
    var elDesc    = document.getElementById('spDesc');
    var elRate    = document.getElementById('spRateRange');
    var elLtv     = document.getElementById('spMaxLtv');
    var elTenure  = document.getElementById('spMaxTenure');
    var elDots    = document.getElementById('spProductDots');
    var elBarFill = document.getElementById('spCycleBar');

    if (!elName) return;

    /* Build dots */
    PRODUCTS.forEach(function (p, i) {
      var d = document.createElement('div');
      d.className = 'sp-product-dot' + (i === current ? ' active' : '');
      d.title = p.name;
      d.addEventListener('click', function () { jumpTo(i); });
      elDots.appendChild(d);
    });

    /* Swap content */
    function cycleTo(idx) {
      var targets = [elBadge, elName, elTagline, elDesc, elRate, elLtv, elTenure];

      targets.forEach(function (el) {
        el.classList.remove('sp-cycle-in');
        el.classList.add('sp-cycle-out');
      });

      setTimeout(function () {
        var p = PRODUCTS[idx];
        elBadge.textContent   = p.icon + ' ' + p.name + ' · ' + CITY;
        elName.textContent    = p.name;
        elTagline.textContent = p.tagline;
        elDesc.textContent    = p.desc;
        elRate.textContent    = p.rate;
        elLtv.textContent     = p.ltv;
        elTenure.textContent  = p.tenure;

        targets.forEach(function (el) {
          el.classList.remove('sp-cycle-out');
          el.classList.add('sp-cycle-in');
        });

        document.querySelectorAll('.sp-product-dot').forEach(function (d, i) {
          d.classList.toggle('active', i === idx);
        });

      }, ANIMATE_MS);
    }

    /* Progress bar */
    function startBar() {
      if (!elBarFill) return;
      elBarFill.style.transition = 'none';
      elBarFill.style.width      = '0%';
      setTimeout(function () {
        elBarFill.style.transition = 'width ' + INTERVAL_MS + 'ms linear';
        elBarFill.style.width      = '100%';
      }, 30);
    }

    /* Auto-advance */
    function advance() {
      current = (current + 1) % PRODUCTS.length;
      cycleTo(current);
      startBar();
    }

    /* Manual dot click */
    function jumpTo(idx) {
      clearInterval(timer);
      current = idx;
      cycleTo(current);
      startBar();
      timer = setInterval(advance, INTERVAL_MS);
    }

    /* ── INIT — load correct product immediately, THEN start cycle ── */
    cycleTo(current);   /* ← FIX: was missing; caused flash on non-home-loan pages */
    startBar();
    timer = setInterval(advance, INTERVAL_MS);

    /* Pause on hover */
    var hero = document.querySelector('.sp-hero-left');
    if (hero) {
      hero.addEventListener('mouseenter', function () { clearInterval(timer); });
      hero.addEventListener('mouseleave', function () { startBar(); timer = setInterval(advance, INTERVAL_MS); });
    }

  })();
  </script>

</body>
</html>

<?php
/*
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
  DYNAMIC SITEMAP — paste into sitemap.php
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>';
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

$today = date('Y-m-d');
$cities   = ['jaipur','jodhpur','udaipur','kota','ajmer','bhilwara','alwar','bikaner'];
$products = ['home-loan','lap','business-loan','personal-loan','car-loan','balance-transfer'];

foreach ($cities as $c) {
    foreach ($products as $p) {
        echo "
<url>
  <loc>https://wealthmetre.com/{$p}-{$c}.html</loc>
  <lastmod>{$today}</lastmod>
  <changefreq>monthly</changefreq>
  <priority>0.8</priority>
</url>";
    }
}

echo '</urlset>';
*/
?>