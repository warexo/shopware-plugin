import { resolve } from 'path';

process.env.ADMIN_PATH = process.env.ADMIN_PATH
    || resolve('../../../../../../../vendor/shopware/administration/Resources/app/administration');

export default {
    preset: '@shopware-ag/jest-preset-sw6-admin',
    globals: {
        adminPath: process.env.ADMIN_PATH,
    },

    testMatch: [
        '<rootDir>/src/**/*.spec.js',
        '<rootDir>/src/**/*.spec.ts',
    ],

    collectCoverageFrom: [
        '<rootDir>/src/**/*.js',
        '<rootDir>/src/**/*.ts',
        '!<rootDir>/src/**/*.spec.js',
        '!<rootDir>/src/**/*.spec.ts',
    ],

    transformIgnorePatterns: [
        '/node_modules/(?!(@shopware-ag/meteor-component-library|@shopware-ag/meteor-icon-kit|uuidv7)/)',
    ],

    moduleNameMapper: {
        '^WarexoAdministration(.*)$': '<rootDir>/src$1',
        '^src(.*)$': `${process.env.ADMIN_PATH}/src$1`,
        '^@shopware-ag/meteor-admin-sdk/es/(.*)': `${process.env.ADMIN_PATH}/node_modules/@shopware-ag/meteor-admin-sdk/umd/$1`,
        '^@shopware-ag/meteor-component-library$': `${process.env.ADMIN_PATH}/node_modules/@shopware-ag/meteor-component-library/dist/common/index.js`,
        'vue$': `${process.env.ADMIN_PATH}/node_modules/vue/dist/vue.cjs.js`,
        '^@vue/test-utils$': `${process.env.ADMIN_PATH}/node_modules/@vue/test-utils/dist/vue-test-utils.cjs.js`,
        '^lodash-es/(.*)$': 'lodash/$1',
    },

    testEnvironmentOptions: {
        customExportConditions: ['node', 'node-addons'],
    },
};
