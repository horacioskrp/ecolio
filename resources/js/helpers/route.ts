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

            // Classrooms routes
            'classrooms.index': '/classrooms',
            'classrooms.create': '/classrooms/create',
            'classrooms.store': '/classrooms',
            'classrooms.show': `/classrooms/${params}`,
            'classrooms.edit': `/classrooms/${params}/edit`,
            'classrooms.update': `/classrooms/${params}`,
            'classrooms.destroy': `/classrooms/${params}`,

            // Classroom Types routes
            'classroom-types.index': '/classroom-types',
            'classroom-types.create': '/classroom-types/create',
            'classroom-types.store': '/classroom-types',
            'classroom-types.show': `/classroom-types/${params}`,
            'classroom-types.edit': `/classroom-types/${params}/edit`,
            'classroom-types.update': `/classroom-types/${params}`,
            'classroom-types.destroy': `/classroom-types/${params}`,

            // Roles routes
            'roles.index': '/roles',
            'roles.create': '/roles/create',
            'roles.store': '/roles',
            'roles.show': `/roles/${params}`,
            'roles.edit': `/roles/${params}/edit`,
            'roles.update': `/roles/${params}`,
            'roles.destroy': `/roles/${params}`,

            // Permissions routes
            'permissions.index': '/permissions',
            'permissions.create': '/permissions/create',
            'permissions.store': '/permissions',
            'permissions.show': `/permissions/${params}`,
            'permissions.edit': `/permissions/${params}/edit`,
            'permissions.update': `/permissions/${params}`,
            'permissions.destroy': `/permissions/${params}`,

            // Users routes
            'users.index': '/users',
            'users.create': '/users/create',
            'users.store': '/users',
            'users.show': `/users/${params}`,
            'users.edit': `/users/${params}/edit`,
            'users.update': `/users/${params}`,
            'users.destroy': `/users/${params}`,
            
            // Add more routes as needed
        };
        
        const routePath = routeMap[name];
        if (routePath) {
            return baseUrl + routePath;
        }
        
        throw new Error(`Unknown route: ${name}`);
    }
}
