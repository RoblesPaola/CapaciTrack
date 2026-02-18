// 1️⃣ Cargar cursos
fetch("/capacitrack/backend/cursos/listar_cursos.php")
  .then(res => res.json())
  .then(cursos => {
    const contenedor = document.getElementById("listaCursos");
    contenedor.innerHTML = "";

    cursos.forEach(curso => {
      contenedor.innerHTML += `
        <div class="curso-card">
          <h3>${curso.titulo}</h3>
          <p>${curso.descripcion}</p>
          <button onclick="inscribirse(${curso.id})">
            Inscribirme
          </button>
        </div>
      `;
    });
  });

// 2️⃣ FUNCIÓN PARA INSCRIBIRSE (VA AQUÍ)
function inscribirse(cursoId) {
  fetch("/capacitrack/backend/cursos/inscribirse.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ curso_id: cursoId })
  })
  .then(res => res.json())
  .then(data => alert(data.message));
}
