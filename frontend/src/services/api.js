// Les composants n’ont pas à connaître le format des requêtes HTTP.
// Envoie une demande au serveur, lit sa réponse JSON et signale les erreurs aux composants.
export async function request(path, { method = "GET", body, signal } = {}) {
  const response = await fetch(path, {
    method, signal,
    headers: body === undefined ? {} : { "Content-Type": "application/json" },
    body: body === undefined ? undefined : JSON.stringify(body),
  });
  // Le serveur renvoie du JSON : on le transforme en objet utilisable dans React.
  let data;
  try {
    data = await response.json();
  } catch {
    throw new Error("Réponse du serveur illisible. Vérifiez que l’API fonctionne.");
  }
  // Une réponse HTTP en erreur devient une erreur que le formulaire peut afficher.
  if (!response.ok) throw new Error(data.message || `Erreur HTTP ${response.status}`);
  return data;
}
// Construit l’adresse de la base en protégeant les caractères spéciaux dans son nom.
export function databasePath(database) {
  return `/api/databases/${encodeURIComponent(database)}`;
}
// Construit l’adresse de la table à partir de sa base et de son nom.
export function tablePath(database, table) {
  return `${databasePath(database)}/tables/${encodeURIComponent(table)}`;
}

export const columnTypes = {
  VARCHAR: "Texte court — VARCHAR(255)", TEXT: "Texte long — TEXT",
  INT: "Nombre entier — INT", DECIMAL: "Nombre décimal — DECIMAL(10,2)", DATE: "Date — DATE",
};
