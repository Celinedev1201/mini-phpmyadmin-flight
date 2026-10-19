import { useEffect, useState } from 'react'

function App() {
  const [databases, setDatabases] = useState([])

  useEffect(() => {
    async function loadDatabases() {
      try {
        const response = await fetch('/api/databases')

        if (!response.ok) {
          throw new Error(`Erreur HTTP ${response.status}`)
        }

        const data = await response.json()

        setDatabases(data.databases)
      } catch (error) {
        console.error('Chargement des bases impossible :', error)
      }
    }

    loadDatabases()
  }, [])

  return (
    <main>
      <h1>Mini phpMyAdmin</h1>
      <p>Nombre de bases : {databases.length}</p>
      <ul>
  {databases.map((database) => (
    <li key={database}>{database}</li>
  ))}
</ul>
    </main>
  )
}

export default App