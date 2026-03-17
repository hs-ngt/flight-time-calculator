window.CitySelect = class CitySelect {
  constructor(selectElement, cities, onChange) {
    this.selectElement = selectElement;
    this.cities = cities;
    this.onChange = onChange;

    const options = cities.map((city) => ({
      value: city.id,
      label: city.label,
      label_en: city.label_en,
      country_name: city.country_name,
      country_name_en: city.country_name_en,
      timezone_id: city.timezone_id,
      aliases_text: Array.isArray(city.aliases) ? city.aliases.join(' ') : '',
      sort_weight: city.sort_weight || 0,
      text: `${city.label} / ${city.country_name}`,
    }));

    this.instance = new TomSelect(this.selectElement, {
      options,
      valueField: 'value',
      labelField: 'text',
      searchField: [
        'label',
        'label_en',
        'country_name',
        'country_name_en',
        'timezone_id',
        'aliases_text',
      ],
      sortField: [
        { field: 'sort_weight', direction: 'desc' },
        { field: '$score', direction: 'desc' },
      ],
      maxItems: 1,
      create: false,
      persist: false,
      closeAfterSelect: true,
      allowEmptyOption: true,
      placeholder: '都市名・国名・空港コードで検索',
      render: {
        option: (data, escape) => `
          <div class="ts-city-option">
            <div class="ts-city-option__title">
              ${escape(data.label)} / ${escape(data.country_name)}
            </div>
            <div class="ts-city-option__sub">
              ${escape(data.label_en)} ・ ${escape(data.timezone_id)}
            </div>
          </div>
        `,
        item: (data, escape) => `
          <div class="ts-city-item">
            ${escape(data.label)} / ${escape(data.country_name)}
          </div>
        `,
        no_results: (data, escape) => `
          <div class="ts-empty px-2 py-2 text-secondary small">
            「${escape(data.input)}」に一致する地点が見つかりませんでした。
          </div>
        `,
      },
      onChange: (value) => {
        const city = this.cities.find((item) => item.id === value) || null;
        this.onChange?.(city);
      },
    });
  }

  setValueById(cityId) {
    if (!cityId) {
      this.instance.clear(true);
      this.onChange?.(null);
      return;
    }

    const city = this.cities.find((item) => item.id === cityId) || null;
    if (!city) {
      this.instance.clear(true);
      this.onChange?.(null);
      return;
    }

    this.instance.setValue(cityId, true);
    this.onChange?.(city);
  }

  getValue() {
    return this.instance.getValue();
  }

  clear() {
    this.instance.clear(true);
    this.onChange?.(null);
  }

  focus() {
    this.instance.focus();
  }
};
