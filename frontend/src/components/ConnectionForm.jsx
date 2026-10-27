import { useState } from "react";
import { request } from "../services/api";
import Feedback from "./Feedback";
// Affiche les champs de connexion et permet de tester les informations sans les enregistrer.
function ConnectionForm() {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");
  const [success, setSuccess] = useState("");

  const [connection, setConnection] = useState({
    name: "",
    engine: "mysql",
    host: "",
    port: "3306",
    database: "",
    username: "",
    password: "",
  });
  // Recopie la valeur saisie dans le bon champ de la connexion.
  function handleChange(event) {
    const { name, value } = event.target;
    setConnection((previous) => ({
      ...previous,
      [name]: value,
    }));
  }
  // Change le moteur et remet son port habituel : 3306 pour MySQL, 5432 pour PostgreSQL.
  function handleEngineChange(event) {
    const engine = event.target.value;
    setConnection((previous) => ({
      ...previous,
      engine,
      port: engine === "mysql" ? "3306" : "5432",
    }));
  }
  // Envoie les informations au test de connexion et affiche la réponse du serveur.
  async function handleSubmit(event) {
    event.preventDefault();
    if (loading) return;
    setLoading(true);
    setError("");
    setSuccess("");

    try {
      const data = await request("/api/connections/test", {
        method: "POST",
        body: connection,
      });
      setSuccess(data.message);
    } catch (error) {
      setError(error.message);
    } finally {
      setLoading(false);
    }
  }

  const fields = [
    { name: "name", label: "Nom de la connexion", type: "text" },
    { name: "host", label: "Adresse du serveur", type: "text" },
    { name: "port", label: "Port", type: "number" },
    { name: "database", label: "Nom de la base", type: "text" },
    { name: "username", label: "Utilisateur", type: "text" },
    { name: "password", label: "Mot de passe", type: "password" },
  ];

  return (
    <section
      className="table-panel connection-panel"
      aria-labelledby="connection-title"
    >
      <div className="panel-heading">
        <h2 id="connection-title">Ajouter une connexion</h2>
      </div>
      <form className="connection-form" onSubmit={handleSubmit}>
        <div className="connection-field">
          <label htmlFor="connection-engine">Moteur</label>
          <select
            id="connection-engine"
            name="engine"
            value={connection.engine}
            onChange={handleEngineChange}
          >
            <option value="mysql">MySQL</option>
            <option value="postgres">PostgreSQL</option>
          </select>
        </div>
        {fields.map((field) => (
          <div
            key={field.name}
            className={`connection-field${field.name === "password" ? " connection-field-wide" : ""}`}
          >
            <label htmlFor={`connection-${field.name}`}>{field.label}</label>
            <input
              id={`connection-${field.name}`}
              name={field.name}
              type={field.type}
              value={connection[field.name]}
              onChange={handleChange}
              min={field.name === "port" ? 1 : undefined}
              max={field.name === "port" ? 65535 : undefined}
              required
            />
          </div>
        ))}
        <button className="action-button" type="submit" disabled={loading}>
          {loading ? "Test en cours…" : "Tester la connexion"}
        </button>
        <Feedback error={error} success={success} />
      </form>
    </section>
  );
}

export default ConnectionForm;