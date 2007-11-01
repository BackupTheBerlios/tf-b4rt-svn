/******************************************************************************
 * $Id$
 * $Date$
 * $Revision$
 ******************************************************************************
 *                                                                            *
 * LICENSE                                                                    *
 *                                                                            *
 * This program is free software; you can redistribute it and/or              *
 * modify it under the terms of the GNU General Public License (GPL)          *
 * as published by the Free Software Foundation; either version 2             *
 * of the License, or (at your option) any later version.                     *
 *                                                                            *
 * This program is distributed in the hope that it will be useful,            *
 * but WITHOUT ANY WARRANTY; without even the implied warranty of             *
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the               *
 * GNU General Public License for more details.                               *
 *                                                                            *
 * To read the license please visit http://www.gnu.org/copyleft/gpl.html      *
 *                                                                            *
 * In addition, as a special exception, the copyright holders give            *
 * permission to link the code of portions of this program with the           *
 * OpenSSL library under certain conditions as described in each              *
 * individual source file, and distribute linked combinations                 *
 * including the two.                                                         *
 *                                                                            *
 * You must obey the GNU General Public License in all respects               *
 * for all of the code used other than OpenSSL.  If you modify                *
 * file(s) with this exception, you may extend this exception to your         *
 * version of the file(s), but you are not obligated to do so.  If you        *
 * do not wish to do so, delete this exception statement from your            *
 * version.  If you delete this exception statement from all source           *
 * files in the program, then also delete it here.                            *
 *                                                                            *
 ******************************************************************************
 * Very minimal, standalone shared_ptr implementation, based on Boost original.
 * Among other things, not thread-safe.
 ******************************************************************************/

#ifndef TFCLILT_SHARED_PTR_HH
#define TFCLILT_SHARED_PTR_HH


namespace detail
{
	// delete helper, check type T is complete.
	template< typename T >
	inline void checked_delete(T* p)
	{
		typedef char type_must_be_complete[sizeof(T) ? 1 : -1];
		(void) sizeof(type_must_be_complete);
		delete p;
	}

	template< typename T >
	class shared_count_impl
	{
	public:

		//
		// Structors.
		//

		explicit shared_count_impl(T* p)
			: m_count(1)
			, m_p(p)
		{}


		//
		// Accessors.
		//

		T* get() const { return m_p; }


		//
		// Methods.
		//

		void add_ref()
		{
			++m_count;
			assert(m_count > 1);
		}

		T* release(bool detach_only = false)
		{
			if (--m_count <= 0)
			{
				assert(m_count == 0);
				if (!detach_only)
					detail::checked_delete(m_p);
				T* const p(m_p);
				delete this;
				return p;
			}
			else
				return m_p;
		}


		//
		// Implementation.
		//

	private:
		long m_count;
		T* m_p;

		// noncopyable
		shared_count_impl(const shared_count_impl& r);
		shared_count_impl& operator=(const shared_count_impl& r);
	};

	template< typename T >
	class shared_count
	{
	private:
		typedef detail::shared_count_impl< T > TImpl;
	public:

		//
		// Structors.
		//

		shared_count()
			: m_p(NULL)
		{}

		explicit shared_count(T* p)
		{
			try
			{
				m_p = new TImpl(p);
			}
			catch (...)
			{
				detail::checked_delete(p);
				throw;
			}
		}

		shared_count(const shared_count& r)
			: m_p(r.m_p)
		{
			if (m_p != NULL)
				m_p->add_ref();
		}

		~shared_count()
		{
			if (m_p != NULL)
				m_p->release();
		}


		//
		// Accessors.
		//

		T* get() const { return m_p == NULL ? NULL : m_p->get(); }


		//
		// Methods.
		//

		T* detach()
		{
			if (m_p == NULL)
				return NULL;
			TImpl* const p(m_p);
			m_p = NULL;
			return p->release(true);
		}


		//
		// Operators.
		//

		shared_count& operator=(const shared_count& r)
		{
			TImpl* const p(r.m_p);
			if (p != m_p)
			{
				if (p != NULL)
					p->add_ref();
				if (m_p != NULL)
					m_p->release();
				m_p = p;
			}

			return *this;
		}

		bool operator==(const shared_count& r) const { return m_p == r.m_p; }
		bool operator< (const shared_count& r) const { return m_p <  r.m_p; }


		//
		// Implementation.
		//

		void swap(shared_count& r)
		{
			::std::swap(m_p, r.m_p);
		}

	private:
		TImpl* m_p;
	};
}


template< typename T >
class shared_ptr
{
private:
	typedef detail::shared_count< T > TCount;
public:
	//typedef T T;
	typedef T element_type;
	typedef T value_type;
	typedef T* pointer;
	typedef T& reference;


	//
	// C-tors.
	//

	shared_ptr()
	{}

	explicit shared_ptr(T* p)
		: m_c(p)
	{}


	//
	// Accessors.
	//

	T* get()        const { return m_c.get(); }
	T* operator->() const { assert(get() != NULL); return  get(); }
	T& operator*()  const { assert(get() != NULL); return *get(); }

	typedef TCount shared_ptr::*unspecified_bool_type;
	operator unspecified_bool_type() const
	{
		return get() == NULL ? NULL : &shared_ptr::m_c;
	}


	//
	// Methods.
	//

	void reset()
	{
		shared_ptr< T >().swap(*this);
	}

	void reset(T* p)
	{
		shared_ptr< T >(p).swap(*this);
	}

	T* detach()
	{
		return m_c.detach();
	}


	//
	// Implementation.
	//

	void swap(shared_ptr& r)
	{
		m_c.swap(r.m_c);
	}

private:
	TCount m_c;
};


#endif
