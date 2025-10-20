import { render } from '@wordpress/element'
import { PressreviewThis } from './Components/PressreviewThis'
import './style.scss'

document.addEventListener('DOMContentLoaded', () => {
  render(<PressreviewThis />, document.getElementById('fcnp-pressreview-this'))
})
