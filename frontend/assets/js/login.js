const API_URL = "http://localhost/capacitrack/backend/auth/login.php";

const form = document.getElementById("loginForm");
const errorMsg = document.getElementById("error");

form.addEventListener("submit", async (e) => {
  e.preventDefault(); // 🔴 CLAVE

  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value.trim();

  errorMsg.textContent = "";

  if (!email || !password) {
    errorMsg.textContent = "Completa todos los campos";
    return;
  }

  try {
    const response = await fetch(API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email, password })
    });

    const raw = await response.text();
    console.log("LOGIN RAW:", raw);

    let data;
    try {
      data = JSON.parse(raw);
    } catch {
      errorMsg.textContent = "Error del servidor (respuesta inválida)";
      return;
    }

    if (!data.success) {
      errorMsg.textContent = data.message;
      return;
    }

    // 🔁 Redirección por rol
    if (data.rol === "admin") {
      window.location.href = "paneladmin.html";
    } else if (data.rol === "moderador") {
      window.location.href = "panelmoderador.html";
    } else {
      window.location.href = "panelusuarios.html";
    }

  } catch (err) {
    console.error(err);
    errorMsg.textContent = "Error de conexión con el servidor";
  }
});
