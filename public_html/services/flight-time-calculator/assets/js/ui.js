window.UI = (() => {
  function renderMeta(targetElement, city, dateValue = '', timeValue = '') {
    if (!targetElement) return;

    if (!city) {
      targetElement.innerHTML = '<div class="meta-card__empty">地点を選択すると、国名・timezone_id・UTCオフセット・サマータイム状態を表示します。</div>';
      return;
    }

    const preview = getTimePreview(city.timezone_id, dateValue, timeValue);

    targetElement.innerHTML = `
      <div class="meta-card__grid">
        <div class="meta-card__row"><span class="meta-card__label">地点</span><strong>${city.label} / ${city.country_name}</strong></div>
        <div class="meta-card__row"><span class="meta-card__label">timezone_id</span><span>${city.timezone_id}</span></div>
        <div class="meta-card__row"><span class="meta-card__label">UTC</span><span>${preview.offsetText}</span></div>
        <div class="meta-card__row"><span class="meta-card__label">サマータイム</span><span>${preview.dstText}</span></div>
      </div>
    `;
  }

  function renderResult(result) {
    document.getElementById('result_empty').classList.add('d-none');
    document.getElementById('result_content').classList.remove('d-none');
    document.getElementById('result_error').classList.add('d-none');
    document.getElementById('result_status').textContent = '計算済み';
    document.getElementById('result_status').className = 'badge text-bg-primary';

    const fieldIds = [
      'duration_text',
      'timezone_diff_text',
      'from_city_label',
      'to_city_label',
      'departure_local_text',
      'arrival_local_text',
      'from_timezone_id',
      'to_timezone_id',
      'departure_utc_text',
      'arrival_utc_text',
      'from_offset_text',
      'to_offset_text',
      'from_dst_text',
      'to_dst_text',
      'arrival_day_offset_text',
    ];

    fieldIds.forEach((id) => {
      const element = document.getElementById(id);
      if (element) {
        element.textContent = result[id] ?? '--';
      }
    });
  }

  function renderError(message) {
    document.getElementById('result_empty').classList.add('d-none');
    document.getElementById('result_content').classList.add('d-none');
    const alert = document.getElementById('result_error');
    alert.textContent = message;
    alert.classList.remove('d-none');
    document.getElementById('result_status').textContent = '入力エラー';
    document.getElementById('result_status').className = 'badge text-bg-danger';
  }

  function resetResult() {
    document.getElementById('result_empty').classList.remove('d-none');
    document.getElementById('result_content').classList.add('d-none');
    document.getElementById('result_error').classList.add('d-none');
    document.getElementById('result_status').textContent = '未計算';
    document.getElementById('result_status').className = 'badge text-bg-light';
  }

  function renderSavedList(container, emptyElement, items, cityMap, options) {
    container.innerHTML = '';
    emptyElement.classList.toggle('d-none', items.length > 0);

    items.forEach((item) => {
      const cityId = typeof item === 'string' ? item : item.id;
      const city = cityMap.get(cityId);
      if (!city) return;

      const wrapper = document.createElement('div');
      wrapper.className = 'saved-item';

      const usedAtText =
        typeof item === 'object' && item.used_at
          ? new Date(item.used_at).toLocaleString('ja-JP', { hour12: false })
          : city.timezone_id;

      wrapper.innerHTML = `
        <div class="saved-item__meta">
          <div class="saved-item__title">${city.label} / ${city.country_name}</div>
          <div class="saved-item__sub">${city.label_en} ・ ${usedAtText}</div>
        </div>
        <div class="saved-item__actions">
          <button type="button" class="btn btn-sm btn-outline-primary" data-role="from">出発へ</button>
          <button type="button" class="btn btn-sm btn-outline-primary" data-role="to">到着へ</button>
          ${options.removable ? '<button type="button" class="btn btn-sm btn-outline-secondary" data-role="remove">削除</button>' : ''}
        </div>
      `;

      wrapper.querySelector('[data-role="from"]').addEventListener('click', () => options.onApply(city.id, 'from'));
      wrapper.querySelector('[data-role="to"]').addEventListener('click', () => options.onApply(city.id, 'to'));

      const removeButton = wrapper.querySelector('[data-role="remove"]');
      if (removeButton) {
        removeButton.addEventListener('click', () => options.onRemove(city.id));
      }

      container.appendChild(wrapper);
    });
  }

  function getTimePreview(timezoneId, dateValue, timeValue) {
    if (!dateValue || !timeValue) {
      return {
        offsetText: '日付と時刻を入力すると表示します。',
        dstText: '日付と時刻を入力すると判定します。',
      };
    }

    try {
      const zoned = resolveZonedDate(timezoneId, dateValue, timeValue);
      const offsetText = getOffsetText(zoned, timezoneId);
      const dstText = getDstText(zoned, timezoneId);

      return { offsetText, dstText };
    } catch (error) {
      return {
        offsetText: '計算結果で表示します。',
        dstText: '計算結果で判定します。',
      };
    }
  }

  function resolveZonedDate(timezoneId, dateValue, timeValue) {
    const [year, month, day] = dateValue.split('-').map(Number);
    const [hour, minute] = timeValue.split(':').map(Number);

    if ([year, month, day, hour, minute].some((value) => Number.isNaN(value))) {
      throw new Error('invalid datetime');
    }

    let guessUtcMs = Date.UTC(year, month - 1, day, hour, minute, 0);

    for (let i = 0; i < 4; i += 1) {
      const parts = getLocalParts(new Date(guessUtcMs), timezoneId);
      const desiredMs = Date.UTC(year, month - 1, day, hour, minute, 0);
      const actualMs = Date.UTC(parts.year, parts.month - 1, parts.day, parts.hour, parts.minute, 0);
      const diffMs = desiredMs - actualMs;

      if (diffMs === 0) {
        break;
      }

      guessUtcMs += diffMs;
    }

    return new Date(guessUtcMs);
  }

  function getLocalParts(date, timezoneId) {
    const formatter = new Intl.DateTimeFormat('en-CA', {
      timeZone: timezoneId,
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      hourCycle: 'h23',
    });

    const map = {};
    formatter.formatToParts(date).forEach((part) => {
      if (part.type !== 'literal') {
        map[part.type] = part.value;
      }
    });

    return {
      year: Number(map.year),
      month: Number(map.month),
      day: Number(map.day),
      hour: Number(map.hour),
      minute: Number(map.minute),
    };
  }

  function getOffsetText(date, timezoneId) {
    const formatter = new Intl.DateTimeFormat('en-US', {
      timeZone: timezoneId,
      timeZoneName: 'shortOffset',
      hour: '2-digit',
    });

    const part = formatter.formatToParts(date).find((item) => item.type === 'timeZoneName');
    if (!part) return '計算結果で表示します。';

    return part.value.replace('GMT', 'UTC');
  }

  function getDstText(date, timezoneId) {
    const longName = getLongTimeZoneName(date, timezoneId);
    const selectedOffset = getOffsetText(date, timezoneId);
    const janOffset = getOffsetText(resolveZonedDate(timezoneId, `${date.getUTCFullYear()}-01-15`, '12:00'), timezoneId);
    const julOffset = getOffsetText(resolveZonedDate(timezoneId, `${date.getUTCFullYear()}-07-15`, '12:00'), timezoneId);
    const hasSeasonalChange = janOffset !== julOffset;

    if (/Daylight|Summer/i.test(longName)) {
      return '実施中';
    }

    if (hasSeasonalChange) {
      return '現在は通常時間';
    }

    if (/Standard/i.test(longName) && selectedOffset === janOffset && selectedOffset === julOffset) {
      return '対象外';
    }

    return '対象外';
  }

  function getLongTimeZoneName(date, timezoneId) {
    const formatter = new Intl.DateTimeFormat('en-US', {
      timeZone: timezoneId,
      timeZoneName: 'long',
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
    });

    const part = formatter.formatToParts(date).find((item) => item.type === 'timeZoneName');
    return part ? part.value : '';
  }

  return {
    renderMeta,
    renderResult,
    renderError,
    resetResult,
    renderSavedList,
  };
})();
