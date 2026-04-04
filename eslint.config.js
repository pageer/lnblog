export default [
  {
    files: ["**/*.js"],

    languageOptions: {
      ecmaVersion: 5,
      sourceType: "script",

      globals: {
        // Browser globals
        window: "readonly",
        document: "readonly",
        navigator: "readonly",
        location: "readonly",
        console: "readonly",
        localStorage: "readonly",
        setTimeout: "readonly",
        alert: "readonly",
        confirm: "readonly",
        File: "readonly",

        // jQuery
        $: "readonly",
        jQuery: "readonly",

        // Dropzone
        Dropzone: "readonly"
      }
    },

    rules: {
      "no-var": "off",
      "prefer-const": "off",
      "no-unused-vars": ["warn", { vars: "all", args: "none" }],
      "no-console": "off",
      "no-undef": "warn",
      "eqeqeq": "warn",
      "curly": "warn",
      "strict": ["warn", "global"],
      "no-empty": "warn",
      "no-trailing-spaces": "warn",
      "semi": ["warn", "always"],

      // Disable no-redeclare for project-specific globals
      "no-redeclare": [
        "warn",
        {
          "builtinGlobals": false
        }
      ]
    }
  }
];
