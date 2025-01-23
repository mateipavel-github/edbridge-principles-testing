import Tool from './pages/Tool'

Nova.booting((app, store) => {
  Nova.inertia('GeneralTools', Tool)
})

Nova.booting((Vue, router, store) => {
    router.addRoutes([
        {
            name: 'general-tools',
            path: '/general-tools',
            component: require('./components/Tool'),
        },
    ])
})
