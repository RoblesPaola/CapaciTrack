function login() {
  const user = document.getElementById("user").value;
  const password = document.getElementById("password").value;

  if (!user || !password) {
    alert("Por favor llena todos los campos");
    return;
  }

  // Por ahora solo simulamos el login
  alert("Login correcto (simulado)");
}
