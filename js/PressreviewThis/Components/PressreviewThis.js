import { Component } from '@wordpress/element'
import { TagsInput } from 'react-tag-input-component'
import apiFetch from '@wordpress/api-fetch'
import { __ } from '@wordpress/i18n'

const queryString = require('query-string')

export class PressreviewThis extends Component {
  state = {
    title: '',
    description: '',
    url: '',
    tags: [],
    isLoading: false
  }

  componentDidMount() {
    const parsed = queryString.parse(window.location.search)

    if (parsed.t) {
      this.setState({
        title: parsed.t
      })
    }

    if (parsed.d) {
      this.setState({
        description: parsed.d
      })
    }

    if (parsed.u) {
      this.setState({
        url: parsed.u
      })
    }
  }

  onSubmit = () => {
    const { title, description, url, tags } = this.state

    this.setState({
      isLoading: true
    })

    apiFetch({
      method: 'POST',
      path: '/fcnpressespiegel/v1/pressreview/add',
      data: {
        title,
        description,
        url,
        tags
      }
    })
      .then(() => {
        window.close()
      })
      .catch((error) => {
        if (error.message) {
          alert(error.message)
        }

        console.error(error)
      })
      .finally(() => {
        this.setState({
          isLoading: false
        })
      })
  }

  render() {
    const { title, description, tags, isLoading } = this.state

    return (
      <>
        {isLoading && (
          <div className='progress-bar striped animated'>
            <span className='progress-bar-green' style={{ width: '100%' }} />
          </div>
        )}

        <div className='form-control'>
          <input
            className='input'
            value={title}
            placeholder='Title'
            disabled={isLoading}
            onChange={(e) => this.setState({ title: e.target.value })}
            type='text'
          />
        </div>
        <div className='form-control'>
          <textarea
            className='input'
            rows='8'
            cols='40'
            disabled={isLoading}
            value={description}
            onChange={(e) => this.setState({ description: e.target.value })}
            placeholder='Beschreibung/Teaser'
          />
        </div>
        <div className='form-control'>
          <TagsInput
            value={tags}
            name='tags'
            disabled={isLoading}
            onChange={(tags) => this.setState({ tags })}
            placeHolder='Tags'
          />
        </div>
        <button
          onClick={this.onSubmit}
          disabled={isLoading}
          className='button-primary button-large'
        >
          {__('Zum Pressespiegel hinzufügen', 'clubfansunited')}
        </button>
      </>
    )
  }
}
