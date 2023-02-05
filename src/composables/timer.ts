import { ref } from 'vue';
import { noop } from '../utils';

const TIMER_INTERVAL = 1000;

export const useTimer = (interval: number) => {
  const timeInSeconds = ref(0);
  const timerId = ref<number>();

  const start = (handler = noop) => {
    timerId.value = setInterval(() => {
      timeInSeconds.value += 1;

      if (timeInSeconds.value % interval === 0)
        handler();
    }, TIMER_INTERVAL);
  };

  const stop = () => {
    if (!timerId.value)
      return ;

    clearInterval(timerId.value);
    timerId.value = undefined;
  }

  const reset = () => {
    stop();

    timeInSeconds.value = 0;
  };

  const restart = () => {
    reset();
    start();
  };

  return {
    timeInSeconds,

    start,
    stop,
    restart,
    reset,
  }
}
