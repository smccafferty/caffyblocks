module.exports = (grunt) ->
  grunt.initConfig
    watch:
      compass:
        files: [
          'sass/**/*.scss'
        ]
        tasks: ['compass:staging']
    compass:
      options:
        config: 'config.rb'
        force: true
      production:
        options:
          environment: 'production'
      staging:
        options:
          environment: 'development'
    build:
      options:
        default: 'staging'
      production: [
        'compass:production'
        'composer:install'
      ]
      staging: [
        'compass:staging'
        'composer:install'
      ]

  grunt.loadNpmTasks 'grunt-voce-plugins'

  grunt.registerTask 'default', [
    'build:staging'
  ]