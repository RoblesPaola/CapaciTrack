import { supabase } from "../../backend/supabase.js";

// Función para cambiar rol
async function cambiarRol(userId) {
  const { error } = await supabase
    .from("profiles")
    .update({ rol: "moderador" })
    .eq("id", userId);

  if (error) {
    alert("Error al cambiar rol");
    console.error(error);
  } else {
    alert("Rol actualizado correctamente");
  }
}

// hacerla accesible al HTML
window.cambiarRol = cambiarRol;
