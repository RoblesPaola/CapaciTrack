
const API_URL = "/capacitrack/backend/auth/register.php";

const form = document.getElementById("registerForm");
const msg = document.getElementById("msg");

form.addEventListener("submit", async (e) => {
  e.preventDefault();

  const nombre = document.getElementById("nombre").value.trim();
  const email = document.getElementById("email").value.trim();
  const password = document.getElementById("password").value.trim();
  const rol = document.getElementById("rol").value;

  try {
    const response = await fetch(API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ nombre, email, password, rol })
    });

    const raw = await response.text();
    console.log("REGISTER RAW:", raw);

    const data = JSON.parse(raw);

    msg.textContent = data.message;
    msg.style.color = data.success ? "green" : "red";

    if (data.success) {
      setTimeout(() => {
        window.location.href = "index.html";
      }, 1500);
    }

  } catch (err) {
    console.error(err);
    msg.textContent = "Error de conexión con el servidor";
  }
});
