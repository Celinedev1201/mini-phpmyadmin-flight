import CreateDatabase from "./components/CreateDatabase";

import { useEffect, useState } from "react";

function App() {
  const [databases, setDatabases] = useState([]);
  const [tables, setTables] = useState([]);
  const [selectedDatabase, setSelectedDatabase] = useState("");

  async function loadDatabases() {
    try {
      const response = await fetch("/api/databases");

      if (!response.ok) {
        throw new Error(`Erreur HTTP ${response.status}`);
      }

      const data = await response.json();
      setDatabases(data.databases);
    } catch (error) {
      console.error("Chargement des bases impossible :", error);
    }
  }

  useEffect(() => {
    loadDatabases();
  }, []);

  async function loadTables(database) {
    const response = await fetch(
      `/api/databases/${encodeURIComponent(database)}/tables`,
    );

    const data = await response.json();

    setTables(data.tables);
    setSelectedDatabase(database);
  }

  return (
    <div className="workspace">
      <aside className="sidebar">
        <div className="brand">
          <span className="eyebrow">L’atelier des données</span>
          <h1>
            Mini
            <br />
            phpMyAdmin<span>.</span>
          </h1>
          <p>par Théoa</p>
        </div>

        <CreateDatabase onCreated={loadDatabases} />
        <nav aria-label="Bases de données">
          <div className="nav-heading">
            <span>Bases de données</span>
            <span className="count">{databases.length}</span>
          </div>

          <ul className="database-list">
            {databases.map((database, index) => (
              <li key={database}>
                <button
                  className={
                    selectedDatabase === database
                      ? "database-button is-active"
                      : "database-button"
                  }
                  aria-current={
                    selectedDatabase === database ? "true" : undefined
                  }
                  onClick={() => loadTables(database)}
                >
                  <span className="database-number">
                    {String(index + 1).padStart(2, "0")}
                  </span>
                  <span className="database-name">{database}</span>
                  <span aria-hidden="true">↗</span>
                </button>
              </li>
            ))}
          </ul>
        </nav>

        <div className="sidebar-footer">
          <span className="footer-line" />
          <p>Explorer. Comprendre. Construire.</p>
          <span>React · FlightPHP · MySQL</span>
        </div>
      </aside>

      <main className="main-content">
        <header className="topbar">
          <span>ESPACE DE TRAVAIL</span>
          <span className="environment">Développement local</span>
        </header>

        <section className="explorer" aria-labelledby="explorer-title">
          <div className="section-heading">
            <div>
              <p className="eyebrow">Explorateur / MySQL</p>
              <h2 id="explorer-title">
                {selectedDatabase || "Vos données, autrement."}
              </h2>
              <p className="section-description">
                {selectedDatabase
                  ? "Les tables et vues de votre base, réunies au même endroit."
                  : "Sélectionnez une base pour explorer sa structure."}
              </p>
            </div>

            {selectedDatabase && (
              <div className="table-count">
                <strong>{tables.length}</strong>
                <span>tables & vues</span>
              </div>
            )}
          </div>

          {selectedDatabase ? (
            <div className="table-panel">
              <div className="panel-heading">
                <span>Structure de la base</span>
                <span>Nom</span>
              </div>

              {tables.length > 0 ? (
                <ul className="table-list">
                  {tables.map((table, index) => (
                    <li key={table} className="table-row">
                      <span className="table-number">
                        {String(index + 1).padStart(2, "0")}
                      </span>
                      <span className="table-symbol" aria-hidden="true">
                        ▦
                      </span>
                      <span className="table-name">{table}</span>
                    </li>
                  ))}
                </ul>
              ) : (
                <p className="empty-message">
                  Cette base ne contient aucune table accessible.
                </p>
              )}
            </div>
          ) : (
            <div className="welcome">
              <span className="welcome-symbol" aria-hidden="true">
                ✧
              </span>
              <p>
                Chaque projet commence
                <br />
                par une <em>structure.</em>
              </p>
              <span>Choisissez votre base dans le menu.</span>
            </div>
          )}
        </section>

        <footer className="main-footer">
          <span>MINI PHPMYADMIN</span>
          <span>Une création Théoa</span>
        </footer>
      </main>
    </div>
  );
}

export default App;
