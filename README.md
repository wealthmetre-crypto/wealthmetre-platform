# WealthMetre — AI-Powered Loan Advisory Platform
## IIT Capstone Project | Module 6 | Project 05: Domain Knowledge Co-Pilot

**Live URL:** https://wealthmetre.com  
**Staging URL:** https://staging.wealthmetre.com  
**n8n Automation:** https://automation.wealthmetre.com  

---

## Problem
India has 500+ banks and NBFCs — each with different CIBIL requirements, property types, income criteria. Loan agents manually match customer profiles to lenders every day using WhatsApp forwards and Excel sheets. Slow, inaccurate, and expensive.

## Solution
WealthMetre uses a hybrid RAG pipeline — SQL filtering + Qdrant vector search over 99+ lender policy corpus — to match any customer profile to the right lender in seconds.

---

## Architecture
## Tech Stack
| Layer | Technology |
|---|---|
| Frontend | HTML, CSS, JavaScript |
| Backend | PHP 8.1 + MySQL 8.0 |
| AI | Claude API (Anthropic) + OpenAI GPT-4 |
| Vector DB | Qdrant |
| Automation | n8n |
| Server | Ubuntu 22.04 VPS |

## Key Features
- Diva AI chatbot — conversational loan advisor with voice input
- AI Policy Parser — Claude extracts 40+ fields from raw lender policies
- Lender Intelligence — 99+ lenders, 50+ Qdrant parameters per vector
- Partner Portal — 13 partners, 700+ leads, commission tracking
- n8n Automation — content generation, follow-up reminders
- Content Studio — AI SEO blogs and social media posts

## Project 05 Mapping
| Requirement | Implementation |
|---|---|
| Knowledge base from documents | Claude parses policies → MySQL + Qdrant |
| Chat interface | Diva AI chatbot |
| Answers with citations | Lender match reasons from policy corpus |
| Multiple corpora | Home loan, LAP, Business loan, Institutional |
| Voice questions (stretch) | Diva Talk button |

## Live URLs
- Production: https://wealthmetre.com
- Automation: https://automation.wealthmetre.com

## Numbers
- 99+ lenders | 50+ Qdrant parameters | 700+ leads | 13 partners | 8 loan categories
