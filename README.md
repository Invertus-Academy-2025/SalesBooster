# SalesBooster Module

## Overview

**SalesBooster** is a PrestaShop module that helps store administrators analyze sales trends using the OpenAI API. It identifies products with decreasing sales performance and allows admins to promote these products using targeted discounts and a carousel display.

---

## Features

- 📊 **Sales Analysis**: Uses OpenAI API to analyze sales data and highlight products with declining trends.
- 🎯 **Product Selection**: Admin can select which products to feature based on the analysis.
- 🏷️ **Add Discounts**: Easily apply discounts to selected products from the admin panel.
- 🛒 **Cart Integration**: Promotional products are shown in a carousel view in the cart before checkout, boosting visibility and conversions.

---

## Admin Panel Screenshots

### Sales Analysis & Configuration Page
![Admin Panel](https://github.com/user-attachments/assets/d5e15b79-3f23-4487-aec5-e52175c094cd)

---

### Synchronize & Analyze Sales
Synchronize data with your store and analyze sales trends using OpenAI.

![Synchronize & Analyze](https://github.com/user-attachments/assets/719b85fa-95e0-4882-91b5-ce64368c369d)

---

### Manage Products for Carousel
Select which products to display in the promotional carousel shown in the cart. Can also add discounts.

![Manage Carousel Products](https://github.com/user-attachments/assets/2b3e9039-1948-427f-8d11-c537e76dc99b)

---

## Front Office Preview

### Product Carousel in Cart
![Carousel View](https://github.com/user-attachments/assets/a46938f6-f027-44b4-a66a-99388e5a2523)

---

## Installation

1. Download Prestashop and symfony-app branch called "SalesBooster". Install them using docker with the correct .env and compose.yml files.
2. Upload the module to your `/modules` directory.
3. Install it from the PrestaShop admin panel.


---

## Notes

- Requires to Run Symfony-app container using SalesBooster Branch for api calls.
- Requires the correct .env and compose.yml files for both projects.
- Sales data of more than a few days must be available for accurate analysis.

---

## Authors

This module was created during the **Invertus 2025 PrestaShop Academy** as part of a learning project.  
Developed by:

- Ignas
- Domas

