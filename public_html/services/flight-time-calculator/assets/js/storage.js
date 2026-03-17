window.StorageService = (() => {
  const KEYS = {
    favorites: 'flightcalc:favorites',
    recent: 'flightcalc:recentCities',
    lastInputs: 'flightcalc:lastInputs',
  };

  function isAvailable() {
    try {
      const key = '__flightcalc_test__';
      localStorage.setItem(key, '1');
      localStorage.removeItem(key);
      return true;
    } catch (error) {
      return false;
    }
  }

  function readJson(key, fallback) {
    if (!isAvailable()) return fallback;
    try {
      const raw = localStorage.getItem(key);
      if (!raw) return fallback;
      const parsed = JSON.parse(raw);
      return parsed ?? fallback;
    } catch (error) {
      return fallback;
    }
  }

  function writeJson(key, value) {
    if (!isAvailable()) return false;
    try {
      localStorage.setItem(key, JSON.stringify(value));
      return true;
    } catch (error) {
      return false;
    }
  }

  function getFavorites() {
    return readJson(KEYS.favorites, []);
  }

  function setFavorites(ids) {
    return writeJson(KEYS.favorites, ids);
  }

  function addFavorite(id) {
    const current = getFavorites();
    if (!current.includes(id)) current.unshift(id);
    setFavorites(current.slice(0, 20));
  }

  function removeFavorite(id) {
    setFavorites(getFavorites().filter((item) => item !== id));
  }

  function clearFavorites() {
    setFavorites([]);
  }

  function getRecent() {
    return readJson(KEYS.recent, []);
  }

  function pushRecent(id) {
    const now = new Date().toISOString();
    const current = getRecent().filter((item) => item && item.id !== id);
    current.unshift({ id, used_at: now });
    writeJson(KEYS.recent, current.slice(0, 10));
  }

  function clearRecent() {
    writeJson(KEYS.recent, []);
  }

  function getLastInputs() {
    return readJson(KEYS.lastInputs, null);
  }

  function setLastInputs(payload) {
    writeJson(KEYS.lastInputs, payload);
  }

  function clearLastInputs() {
    if (!isAvailable()) return;
    localStorage.removeItem(KEYS.lastInputs);
  }

  return {
    isAvailable,
    getFavorites,
    setFavorites,
    addFavorite,
    removeFavorite,
    clearFavorites,
    getRecent,
    pushRecent,
    clearRecent,
    getLastInputs,
    setLastInputs,
    clearLastInputs,
  };
})();
