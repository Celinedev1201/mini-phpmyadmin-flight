import { useState } from "react";

function InsertRow({ database, table, columns, onCreated }) {
  const [values, setValues] = useState({});

  const editableColumns = columns.filter(
    (column) => !column.EXTRA.includes("auto_increment"),
  );

  async function handleSubmit(event) {
    event.preventDefault();

    try {
      const response = await fetch(
        `/api/databases/${encodeURIComponent(database)}/tables/${encodeURIComponent(table)}/rows`,
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ values }),
        },
      );

      const data = await response.json();

      if (!response.ok) {
        throw new Error(data.message || `Erreur HTTP ${response.status}`);
      }

      console.log("Réponse de PHP :", data);
      setValues({});
      onCreated?.();
    } catch (error) {
      console.error("Insertion impossible :", error);
    }
  }

  return (
    <form className="insert-row" onSubmit={handleSubmit}>
      <h3>Ajouter une ligne</h3>

      {editableColumns.map((column) => (
        <div key={column.COLUMN_NAME}>
          <label htmlFor={`insert-${column.COLUMN_NAME}`}>
            {column.COLUMN_NAME}
          </label>

          <input
            id={`insert-${column.COLUMN_NAME}`}
            type="text"
            value={values[column.COLUMN_NAME] ?? ""}
            onChange={(event) =>
              setValues((previous) => ({
                ...previous,
                [column.COLUMN_NAME]: event.target.value,
              }))
            }
          />
        </div>
      ))}

      <button type="submit">Ajouter la ligne</button>
    </form>
  );
}

export default InsertRow;
