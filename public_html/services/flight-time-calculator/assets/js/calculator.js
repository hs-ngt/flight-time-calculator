window.CalculatorApi = (() => {
  async function fetchCities() {
    const response = await fetch('./api/cities.php', {
      headers: { 'Accept': 'application/json' },
    });

    const data = await response.json();

    if (!response.ok || !data.ok) {
      throw new Error((data.errors && data.errors[0]) || '地点一覧の取得に失敗しました。');
    }

    return data.items;
  }

  async function calculate(payload) {
    const response = await fetch('./api/calculate.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(payload),
    });

    const data = await response.json();

    if (!response.ok || !data.ok) {
      const message = (data.errors && data.errors[0]) || '計算に失敗しました。';
      const error = new Error(message);
      error.payload = data;
      throw error;
    }

    return data.result;
  }

  return {
    fetchCities,
    calculate,
  };
})();
