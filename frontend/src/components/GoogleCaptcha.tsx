import { useEffect, useRef } from 'react'

type GoogleCaptchaProps = {
  siteKey: string
  onToken: (token: string) => void
}

type Grecaptcha = {
  ready: (callback: () => void) => void
  render: (
    container: HTMLElement,
    parameters: {
      sitekey: string
      callback: (token: string) => void
      'expired-callback': () => void
      'error-callback': () => void
    },
  ) => number
}

declare global {
  interface Window {
    grecaptcha?: Grecaptcha
  }
}

const scriptId = 'google-recaptcha-script'
const scriptSource = 'https://www.google.com/recaptcha/api.js?render=explicit'

function GoogleCaptcha({ siteKey, onToken }: GoogleCaptchaProps) {
  const containerRef = useRef<HTMLDivElement>(null)

  useEffect(() => {
    let cancelled = false

    const renderCaptcha = () => {
      if (cancelled || !containerRef.current || !window.grecaptcha) {
        return
      }

      containerRef.current.replaceChildren()

      window.grecaptcha.render(containerRef.current, {
        sitekey: siteKey,
        callback: onToken,
        'expired-callback': () => onToken(''),
        'error-callback': () => onToken(''),
      })
    }

    const existingScript = document.getElementById(scriptId)

    if (window.grecaptcha) {
      window.grecaptcha.ready(renderCaptcha)
    } else if (existingScript) {
      existingScript.addEventListener('load', renderCaptcha)
    } else {
      const script = document.createElement('script')
      script.id = scriptId
      script.src = scriptSource
      script.async = true
      script.defer = true
      script.addEventListener('load', renderCaptcha)
      document.head.appendChild(script)
    }

    return () => {
      cancelled = true
      existingScript?.removeEventListener('load', renderCaptcha)
    }
  }, [onToken, siteKey])

  return (
    <div
      ref={containerRef}
      aria-label="Google reCAPTCHA"
      className="google-captcha"
    />
  )
}

export default GoogleCaptcha
