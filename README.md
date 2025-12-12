# ⚽ FM Planner Pro

**FM Planner Pro** is a modern, visual squad planning tool designed to be the ultimate companion for *Football Manager* players. 

It moves away from complex spreadsheets, offering an interactive **Drag & Drop** interface to manage your starting lineup, bench, and transfer targets. Built with simplicity and performance in mind, it uses a local SQLite database, making it portable and easy to set up without complex server configurations.

<img src="https://github.com/user-attachments/assets/5276308a-fd8e-4a44-86d0-5cc12ed534ce" alt="Deskripsi" width="1000"/> 

## ✨ Key Features!

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

---


## 🚀 Installation Guide (Portable Method)

You can run this application as a standalone desktop program using **PHP Desktop**. No XAMPP or complex server setup is required.

### Step 1: Download PHP Desktop
1.  Visit the PHP Desktop From @cztomczak [PHP Desktop Chrome Releases](https://github.com/cztomczak/phpdesktop) page.
2.  Download the latest Releases file (e.g., `phpdesktop-chrome-v...zip`).
3.  **Extract** the downloaded zip file to a folder on your computer (e.g., `C:\FMPlanner`).

### Step 2: Download FM Planner
1.  Download this repository (Click **Code** > **Download ZIP**) or clone it.
2.  Extract the files if you downloaded the ZIP.

### Step 3: Setup Files
1.  Open the **PHP Desktop** folder you extracted in Step 1.
2.  Open the **`www`** folder inside it.
3.  **Delete all files** currently inside the `www` folder (these are just default examples).
4.  **Copy all FM Planner Pro files** (index.php, api.php, style.css, script.js, etc.) into this `www` folder.

### Step 4: Run the App
1.  Go back to the main folder.
2.  Run **`phpdesktop-chrome.exe`**.
3.  The application will launch. On the first run, it will automatically set up the database (`database.sqlite`).

