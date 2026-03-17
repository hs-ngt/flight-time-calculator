document.addEventListener('DOMContentLoaded', async () => {
  const form = document.getElementById('flight-form');
  const fromMeta = document.getElementById('from_meta');
  const toMeta = document.getElementById('to_meta');
  const favoritesList = document.getElementById('favorites_list');
  const favoritesEmpty = document.getElementById('favorites_empty');
  const recentList = document.getElementById('recent_list');
  const recentEmpty = document.getElementById('recent_empty');

  const departureDateInput = document.getElementById('departure_date');
  const departureTimeInput = document.getElementById('departure_time');
  const arrivalDateInput = document.getElementById('arrival_date');
  const arrivalTimeInput = document.getElementById('arrival_time');

  let cities = [];
  let cityMap = new Map();
  let fromSelect = null;
  let toSelect = null;

  function getPayload() {
    return {
      from_id: document.getElementById('from_id').value,
      to_id: document.getElementById('to_id').value,
      departure_date: departureDateInput.value,
      departure_time: departureTimeInput.value,
      arrival_date: arrivalDateInput.value,
      arrival_time: arrivalTimeInput.value,
    };
  }

  function saveLastInputs() {
    StorageService.setLastInputs(getPayload());
  }

  function refreshMeta() {
    UI.renderMeta(fromMeta, cityMap.get(document.getElementById('from_id').value), departureDateInput.value, departureTimeInput.value);
    UI.renderMeta(toMeta, cityMap.get(document.getElementById('to_id').value), arrivalDateInput.value, arrivalTimeInput.value);
  }

  function renderSavedSections() {
    UI.renderSavedList(
      favoritesList,
      favoritesEmpty,
      StorageService.getFavorites(),
      cityMap,
      {
        removable: true,
        onApply: (cityId, target) => {
          if (target === 'from') fromSelect.setValueById(cityId);
          else toSelect.setValueById(cityId);
          refreshMeta();
          saveLastInputs();
        },
        onRemove: (cityId) => {
          StorageService.removeFavorite(cityId);
          renderSavedSections();
        },
      }
    );

    UI.renderSavedList(
      recentList,
      recentEmpty,
      StorageService.getRecent(),
      cityMap,
      {
        removable: false,
        onApply: (cityId, target) => {
          if (target === 'from') fromSelect.setValueById(cityId);
          else toSelect.setValueById(cityId);
          refreshMeta();
          saveLastInputs();
        },
        onRemove: () => {},
      }
    );
  }

  function applyLastInputs() {
    const last = StorageService.getLastInputs();
    if (!last) return;

    if (last.from_id) fromSelect.setValueById(last.from_id);
    if (last.to_id) toSelect.setValueById(last.to_id);

    departureDateInput.value = last.departure_date || '';
    departureTimeInput.value = last.departure_time || '';
    arrivalDateInput.value = last.arrival_date || '';
    arrivalTimeInput.value = last.arrival_time || '';

    refreshMeta();
  }

  function wireMetaRefresh(input) {
    input.addEventListener('change', () => {
      refreshMeta();
      saveLastInputs();
    });
    input.addEventListener('input', () => {
      refreshMeta();
      saveLastInputs();
    });
  }

  function setDefaultDates() {
    const now = new Date();
    const yyyy = now.getFullYear();
    const mm = String(now.getMonth() + 1).padStart(2, '0');
    const dd = String(now.getDate()).padStart(2, '0');
    const defaultDate = `${yyyy}-${mm}-${dd}`;

    if (!departureDateInput.value) departureDateInput.value = defaultDate;
    if (!arrivalDateInput.value) arrivalDateInput.value = defaultDate;
    if (!departureTimeInput.value) departureTimeInput.value = '09:00';
    if (!arrivalTimeInput.value) arrivalTimeInput.value = '12:00';
  }

  async function handleCalculate(event) {
    event?.preventDefault();
    saveLastInputs();

    try {
      const result = await CalculatorApi.calculate(getPayload());
      UI.renderResult(result);

      const payload = getPayload();
      if (payload.from_id) StorageService.pushRecent(payload.from_id);
      if (payload.to_id) StorageService.pushRecent(payload.to_id);
      renderSavedSections();
    } catch (error) {
      UI.renderError(error.message || '計算に失敗しました。');
    }
  }

  try {
    cities = await CalculatorApi.fetchCities();
    cityMap = new Map(cities.map((city) => [city.id, city]));

    fromSelect = new CitySelect(document.getElementById('from_id'), cities, (city) => {
      UI.renderMeta(fromMeta, city, departureDateInput.value, departureTimeInput.value);
      saveLastInputs();
    });

    toSelect = new CitySelect(document.getElementById('to_id'), cities, (city) => {
      UI.renderMeta(toMeta, city, arrivalDateInput.value, arrivalTimeInput.value);
      saveLastInputs();
    });

    setDefaultDates();
    applyLastInputs();
    refreshMeta();
    renderSavedSections();

    form.addEventListener('submit', handleCalculate);

    document.getElementById('swap_button').addEventListener('click', async () => {
      const snapshot = {
        from_id: document.getElementById('from_id').value,
        to_id: document.getElementById('to_id').value,
        departure_date: departureDateInput.value,
        departure_time: departureTimeInput.value,
        arrival_date: arrivalDateInput.value,
        arrival_time: arrivalTimeInput.value,
      };

      fromSelect.setValueById(snapshot.to_id);
      toSelect.setValueById(snapshot.from_id);
      departureDateInput.value = snapshot.arrival_date;
      departureTimeInput.value = snapshot.arrival_time;
      arrivalDateInput.value = snapshot.departure_date;
      arrivalTimeInput.value = snapshot.departure_time;

      refreshMeta();
      saveLastInputs();

      if (!document.getElementById('result_empty').classList.contains('d-none')) return;
      await handleCalculate();
    });

    document.querySelectorAll('.favorite-toggle').forEach((button) => {
      button.addEventListener('click', () => {
        const target = button.dataset.target;
        const cityId = target === 'from' ? document.getElementById('from_id').value : document.getElementById('to_id').value;

        if (!cityId) {
          UI.renderError('お気に入りに追加する前に地点を選択してください。');
          return;
        }

        StorageService.addFavorite(cityId);
        renderSavedSections();
      });
    });

    document.getElementById('clear_favorites').addEventListener('click', () => {
      StorageService.clearFavorites();
      renderSavedSections();
    });

    document.getElementById('clear_recent').addEventListener('click', () => {
      StorageService.clearRecent();
      renderSavedSections();
    });

    document.getElementById('reset_button').addEventListener('click', () => {
      form.reset();
      setDefaultDates();
      fromSelect.setValueById('');
      toSelect.setValueById('');
      StorageService.clearLastInputs();
      refreshMeta();
      UI.resetResult();
    });

    [departureDateInput, departureTimeInput, arrivalDateInput, arrivalTimeInput].forEach(wireMetaRefresh);
  } catch (error) {
    UI.renderError(error.message || '初期化に失敗しました。');
  }
});
