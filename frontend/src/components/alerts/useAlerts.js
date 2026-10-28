import { useEffect, useState } from "react";
import { request } from "../../services/api";

// Une seule source pour le compteur et l’historique, même panneau fermé.
export default function useAlerts() {
  const [alerts, setAlerts] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [version, setVersion] = useState(0);

  useEffect(() => {
    const controller = new AbortController();

    async function loadAlerts() {
      setLoading(true);
      setError("");

      try {
        const data = await request("/api/alerts", {
          signal: controller.signal,
        });

        if (!controller.signal.aborted) {
          setAlerts(data.alerts);
        }
      } catch (error) {
        if (!controller.signal.aborted) {
          setError(error.message);
        }
      } finally {
        if (!controller.signal.aborted) {
          setLoading(false);
        }
      }
    }

    loadAlerts();

    return () => controller.abort();
  }, [version]);

  useEffect(() => {
    const timer = setInterval(() => {
      setVersion((previous) => previous + 1);
    }, 30000);

    return () => clearInterval(timer);
  }, []);

  return { alerts, loading, error, refresh: () => setVersion((previous) => previous + 1) };
}
