import {
    createSearchParamsCache,
    createSerializer,
    parseAsInteger,
    parseAsIsoDate,
    parseAsString
  } from 'nuqs/server';
  
  export const searchParams = {
    page: parseAsInteger.withDefault(1),
    limit: parseAsInteger.withDefault(10),
    q: parseAsString,
    gender: parseAsString,
    categories: parseAsString,
    from_date: parseAsIsoDate, // Added from_date
    to_date: parseAsIsoDate  
  };
  
  export const searchParamsCache = createSearchParamsCache(searchParams);
  export const serialize = createSerializer(searchParams);
  