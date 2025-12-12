# ⚽ FM Planner Pro

**FM Planner Pro** is a modern, visual squad planning tool designed to be the ultimate companion for *Football Manager* players. 

It moves away from complex spreadsheets, offering an interactive **Drag & Drop** interface to manage your starting lineup, bench, and transfer targets. Built with simplicity and performance in mind, it uses a local SQLite database, making it portable and easy to set up without complex server configurations.


## ✨ Key Features

### 🏟️ Squad Management
* **Interactive Pitch View:** Visualize your formation with a dynamic pitch interface.
* **Multi-Squad Support:** Manage both your **1st Team** and **2nd Team** simultaneously.
* **Drag & Drop System:** * Move players between the pitch and the substitutes bench effortlessly.
    * Swap player positions on the fly.
    * Drag players between tables to promote/demote them (e.g., from Subs to 1st Team).

### 📋 Scouting & Transfers
* **Shortlist Mode:** A dedicated simulation area to plan future signings without disrupting your current squad.
* **Players Out:** Track players you intend to sell, including their asking price.
* **Visual Indicators:** Badges for **Home Grown (HG)** status, **Peak Age**, **Wonderkids**, and contract status.

### 🌟 Global Favorites (Dream Team)
* Save your favorite players from different saves or databases into one global "Hall of Fame".
* Drag and drop favorite players onto a fantasy pitch to build your ultimate Dream Team.

### 📊 Analysis Tools
* **Player Comparison:** Head-to-head comparison tool with smart filtering (Squad vs. Shortlist vs. Favorites).
* **Color-Coded Attributes:** Instantly spot the better player with green/black attribute highlighting.

### 🎨 Customization & UI
* **Modern Design:** Clean, "SaaS-like" UI with sticky headers and custom scrollbars.
* **Position Color Coding:** Fully customizable colors for GK, DF, MF, and FW via CSS variables.
* **Responsive:** Works great on desktop and adapts decently to smaller windows.

---

## 🛠️ Tech Stack

* **Backend:** PHP 8.x (Native/Vanilla)
* **Database:** SQLite 3 (Serverless, zero-configuration)
* **Frontend:** HTML5, CSS3 (Grid/Flexbox), Vanilla JavaScript
* **Interactions:** HTML5 Drag and Drop API, Fetch API
