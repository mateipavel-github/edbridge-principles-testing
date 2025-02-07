import Tool from './pages/Tool'
import QueueJobs from './pages/QueueJobs'

Nova.booting((app, store) => {
  Nova.inertia('GeneralTools', Tool)
  Nova.inertia('QueueJobs', QueueJobs)
})


