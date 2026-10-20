import { useEffect, useState } from "react";

function App() {
  const [databases, setDatabases] = useState([]);
  const [tables, setTables] = useState([]);
  const [selectedDatabase, setSelectedDatabase] = useState("");

  useEffect(() => {
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
    <main>
      <h1>Mini phpMyAdmin</h1>
      <p>Nombre de bases : {databases.length}</p>
      <ul>
        {databases.map((database) => (
          <li key={database}>
            <button onClick={() => loadTables(database)}>{database}</button>
          </li>
        ))}
      </ul>

      <h2>
        {selectedDatabase
          ? `Tables de ${selectedDatabase}`
          : "Sélectionne une base"}
      </h2>

      <ul>
        {tables.map((table) => (
          <li key={table}>{table}</li>
        ))}
      </ul>
    </main>
  );
}

export default App;
