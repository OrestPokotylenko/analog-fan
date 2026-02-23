import { ref, computed } from 'vue';

export const CONDITIONS = [
  { value: 'new',      label: 'New / Sealed' },
  { value: 'like_new', label: 'Like New' },
  { value: 'good',     label: 'Good' },
  { value: 'fair',     label: 'Fair' },
  { value: 'poor',     label: 'Poor' },
];

/**
 * Provides reactive filter state and a filteredItems computed.
 *
 * @param {import('vue').Ref<Array>} itemsRef - the raw item list to filter
 */
export function useItemFilters(itemsRef) {
  const minPrice          = ref('');
  const maxPrice          = ref('');
  const selectedConditions = ref([]);
  const selectedTypes      = ref([]);
  const sortOrder          = ref('');   // '' | 'price_asc' | 'price_desc'

  const activeFilterCount = computed(() => {
    let n = 0;
    if (minPrice.value !== '')          n++;
    if (maxPrice.value !== '')          n++;
    if (selectedConditions.value.length) n++;
    if (selectedTypes.value.length)      n++;
    return n;
  });

  function clearFilters() {
    minPrice.value           = '';
    maxPrice.value           = '';
    selectedConditions.value = [];
    selectedTypes.value      = [];
    sortOrder.value          = '';
  }

  const filteredItems = computed(() => {
    const min = minPrice.value !== '' ? parseFloat(minPrice.value) : null;
    const max = maxPrice.value !== '' ? parseFloat(maxPrice.value) : null;

    let result = itemsRef.value.filter(item => {
      if (min !== null && item.price < min) return false;
      if (max !== null && item.price > max) return false;
      if (selectedConditions.value.length && !selectedConditions.value.includes(item.condition)) return false;
      if (selectedTypes.value.length && !selectedTypes.value.includes(item.productTypeId)) return false;
      return true;
    });

    if (sortOrder.value === 'price_asc')  result = [...result].sort((a, b) => a.price - b.price);
    if (sortOrder.value === 'price_desc') result = [...result].sort((a, b) => b.price - a.price);

    return result;
  });

  return {
    minPrice, maxPrice, selectedConditions, selectedTypes,
    sortOrder, filteredItems, activeFilterCount, clearFilters,
  };
}
