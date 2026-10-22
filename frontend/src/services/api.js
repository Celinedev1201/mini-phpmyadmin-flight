// Les composants n’ont pas à connaître le format des requêtes HTTP.
export async function request(path, { method = "GET", body, signal } = {}) {
  const response = await fetch(path, {
    method, signal,
    headers: body === undefined ? {} : { "Content-Type": "application/json" },
    body: body === undefined ? undefined : JSON.stringify(body),
  });
  let data;
  try {
    data = await response.json();
  } catch {
    throw new Error("Réponse du serveur illisible. Vérifiez que l’API fonctionne.");
  }
  if (!response.ok) throw new Error(data.message || `Erreur HTTP ${response.status}`);
  return data;
}

export function databasePath(database) {
  return `/api/databases/${encodeURIComponent(database)}`;
}

export function tablePath(database, table) {
  return `${databasePath(database)}/tables/${encodeURIComponent(table)}`;
}

export const columnTypes = {
  VARCHAR: "Texte court — VARCHAR(255)", TEXT: "Texte long — TEXT",
  INT: "Nombre entier — INT", DECIMAL: "Nombre décimal — DECIMAL(10,2)", DATE: "Date — DATE",
};
