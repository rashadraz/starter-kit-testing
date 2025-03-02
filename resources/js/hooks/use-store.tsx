import { useState, useEffect } from 'react';

// Define generic types for the store and callback
type StoreCallback<T> = (state: T) => unknown;
type Store<T> = (callback: StoreCallback<T>) => unknown;

export const useStore = <T, R>(store: Store<T>, callback: StoreCallback<T>): R | undefined => {
  const result = store(callback);
  const [data, setData] = useState<R>();

  useEffect(() => {
    setData(result as R);
  }, [result]);

  return data;
};