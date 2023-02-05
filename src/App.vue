<template>
  <div v-if="randomImage">
    <img
      :src="randomImage.src"
      alt="image"
    >

    <div
      v-if="isImageLoaded"
      class="view"
    >
      {{ randomImageViewsCount }}
    </div>
  </div>
</template>

<script lang="ts">
import { computed, defineComponent, onMounted, watch, ref, onUnmounted } from 'vue';

import { asyncLoadImage } from './utils';
import { useTimer } from './composables/timer';

const STORAGE_KEY = 'images';
const IMAGE_PLACEHOLDER_SRC = '/img/empty.jpg';
const HANDLE_INTERVAL = 5000;

export default defineComponent({
  name: 'ThaApp',

  setup() {
    const randomId = ref<number | null>(null);
    const randomImage = ref<HTMLImageElement | null>(null);
    const randomImageViewsCount = ref(0);
    const isImageLoaded = ref(false);

    const randomImageSrc = computed(() => randomId.value
      ? `/img/${randomId.value}.jpg`
      : null
    );

    const getRandomId = async () => {
      try {
        const response = await fetch('/api/random-id');
        const json = await response.json();

        randomId.value = json.randomId;
      } catch (err) {
        console.log(err);
      }
    }

    const updateImageViewsCount = async (imageId: number) => {
      const headers = { 'Content-type': 'application/json' };

      try {
        const response = await fetch(`/api/views/${imageId}`, { method: 'post', headers });
        const json = await response.json();

      } catch (err) {
        console.log(err);
      }
    }

    const getImageViewsCount = async (imageId: number) => {
      try {
        const response = await fetch(`/api/views/${imageId}`);
        const json = await response.json();

        randomImageViewsCount.value = json.views;

        const images = { [imageId]: randomImageViewsCount.value };
        localStorage.setItem(STORAGE_KEY, JSON.stringify(images));
      } catch (err) {
        console.log(err);
      }
    }

    const timer = useTimer(HANDLE_INTERVAL);

    watch(randomId, async (newRandomId) => {
      if (!newRandomId || !randomImageSrc.value)
        return;

      asyncLoadImage(randomImageSrc.value).then(async (result) => {
        randomImage.value = result;
        isImageLoaded.value = true;
      }).catch(err => {
        console.log(err);

        randomImage.value = { src: IMAGE_PLACEHOLDER_SRC } as HTMLImageElement;
        isImageLoaded.value = false;
      });
    });

    watch(isImageLoaded, async () => {
      if (!randomId.value)
        return;

      try {
        await updateImageViewsCount(randomId.value);
        await getImageViewsCount(randomId.value);

        timer.start(() => getImageViewsCount(randomId.value as number));
      } catch (err) {
        console.log(err);
      }
    })

    const onStorage = (event: StorageEvent) => {
      const { key, newValue } = event;

      if (key === STORAGE_KEY && newValue && randomId.value) {
        const images = JSON.parse(newValue) || {};
        randomImageViewsCount.value = images[randomId.value] ?? 1;
      }
    }

    onMounted(async () => {
      await getRandomId();

      window.addEventListener('storage', onStorage);
    });

    onUnmounted(() => {
      timer.reset();
      window.removeEventListener('storage', onStorage);
    });

    return {
      randomImage,
      randomImageViewsCount,
      isImageLoaded,
    }
  }
});

</script>


