import { route as ziggyRoute } from 'ziggy-js';

/**
 * Generate a route URL
 * @param name - The route name (e.g., 'schools.index', 'schools.store')
 * @param params - Optional parameters for the route
 * @returns The generated route URL
 */
export function route(name: string, params?: any): string {
    try {
        return ziggyRoute(name, params);
    } catch (e) {
        // Fallback if Ziggy is not available
        // This is a simple fallback that works for basic resource routes
        console.warn(`Route "${name}" not found. Using fallback.`);
        
        const baseUrl = window.location.origin;
        const routeMap: Record<string, string> = {
            // Schools routes
            'schools.index': '/schools',
            'schools.create': '/schools/create',
            'schools.store': '/schools',
            'schools.show': `/schools/${params}`,
            'schools.edit': `/schools/${params}/edit`,
            'schools.update': `/schools/${params}`,
            'schools.destroy': `/schools/${params}`,
            
            // Add more routes as needed
        };
        
        const routePath = routeMap[name];
        if (routePath) {
            return baseUrl + routePath;
        }
        
        throw new Error(`Unknown route: ${name}`);
    }
}
