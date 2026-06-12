# GymPro Deployment Guide

## Quick Start: Deploy to Render

### 1. Create a Render Account
- Go to [render.com](https://render.com) and sign up with GitHub.

### 2. Connect Repository
- Click "New" → "Web Service".
- Select "Build and deploy from a Git repository".
- Choose your GitHub repo (`gymprojects`).
- Select "Docker" as the runtime.
- Render will detect `render.yaml` automatically.

### 3. Review Configuration
- Render will provision:
  - **Web Service**: PHP + Apache (auto-deployed on every push).
  - **MySQL Database**: `gym_booking_system` (auto-created).
  - **Environment Variables**: automatically linked from database.

### 4. Deploy & Seed Database
After Render finishes the initial deploy (5-10 min):
- Open your Render app URL (e.g., `https://gymprojects.onrender.com`).
- Run the migration once to seed users:
  ```
  https://gymprojects.onrender.com/api/migrate.php
  ```
  or open a shell in Render and run:
  ```bash
  php api/migrate.php
  ```

### 5. Login
- Admin: `admin@gympro.com` / `admin123`
- Demo: `john@gympro.com` / `member123`

---

## Local Docker Development

### Prerequisites
- [Docker](https://www.docker.com/products/docker-desktop) installed.

### Run Locally
```bash
docker-compose up --build
```

Open http://localhost:8080 and seed:
```bash
curl http://localhost:8080/api/migrate.php
```

### Stop
```bash
docker-compose down
```

---

## Alternative: Deploy to Docker Hub + Manual VPS

If you want more control:

### Build & Push Image
```bash
docker build -t yourusername/gymprojects:latest .
docker push yourusername/gymprojects:latest
```

### On VPS (e.g., DigitalOcean Droplet)
```bash
# Pull and run
docker pull yourusername/gymprojects:latest
docker run -d \
  -p 80:80 \
  -e DB_HOST=mysql.example.com \
  -e DB_NAME=gym_booking_system \
  -e DB_USER=root \
  -e DB_PASS=your_password \
  yourusername/gymprojects:latest
```

---

## Environment Variables

All DB credentials are read from environment. For local dev, create a `.env` file:
```
DB_HOST=localhost
DB_NAME=gym_booking_system
DB_USER=root
DB_PASS=
DB_CHARSET=utf8mb4
```

**Important**: Never commit `.env` to Git. Render and Docker set these securely.

---

## Troubleshooting

### Migration fails on Render
- Check Render logs: Dashboard → Your Service → Logs tab.
- Ensure MySQL database provisioned (may take a few minutes).
- Try running migration via shell (Render Dashboard → Shell tab):
  ```bash
  php api/migrate.php
  ```

### Database connection error
- Verify `DB_HOST`, `DB_USER`, `DB_PASS` in Render environment.
- Render auto-injects from database definition in `render.yaml`.

### Site shows blank page
- Check Apache error logs in Render logs.
- Ensure index.html exists at root.
